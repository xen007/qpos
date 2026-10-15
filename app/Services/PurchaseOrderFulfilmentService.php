<?php

namespace App\Services;

use App\Models\{PointOfSale, Purchase, PurchaseOrder, PurchaseOrderEvent, PurchaseReceiptItem, User};
use App\Support\QuantityDecimal;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class PurchaseOrderFulfilmentService
{
    public function quantities(PurchaseOrder $order): array
    {
        $ids = $order->items->pluck('purchase_item_id')->filter();
        $received = PurchaseReceiptItem::whereIn('purchase_item_id', $ids)
            ->whereHas('receipt', fn ($q) => $q->where('status', 'received'))
            ->selectRaw('purchase_item_id, SUM(entered_quantity) AS quantity')
            ->groupBy('purchase_item_id')->pluck('quantity', 'purchase_item_id');
        $result = [];
        foreach ($order->items as $line) {
            $qty = BigDecimal::of((string) ($received[$line->purchase_item_id] ?? '0'));
            $remaining = BigDecimal::of($line->quantity_ordered)->minus($line->closed_quantity)->minus($qty);
            $result[$line->id] = ['received' => (string) $qty, 'remaining' => (string) BigDecimal::max('0', $remaining)];
        }
        return $result;
    }

    public function receive(int $id, array $data, int $actor, int $shop): PurchaseOrder
    {
        return DB::transaction(function () use ($id, $data, $actor, $shop) {
            [$order, $hash, $prior] = $this->start($id, 'receive', $data, $actor, $shop, ['purchase_order_receive', 'purchase_receive']);
            if ($prior) return $prior;
            $this->open($order, $data);
            $before = $order->toArray();
            $quantities = $this->quantities($order);
            $items = $order->items->keyBy('id');
            $lines = $changes = $seen = [];
            if (empty($data['items']) || count($data['items']) > 200) $this->fail('replenishment.receipt_lines');
            foreach ($data['items'] as $index => $input) {
                $lineId = (int) ($input['purchase_order_item_id'] ?? 0);
                if (!isset($items[$lineId]) || isset($seen[$lineId])) $this->fail('replenishment.receipt_lines');
                $seen[$lineId] = true;
                $line = $items[$lineId];
                $qty = QuantityDecimal::parse((string) ($input['quantity'] ?? '0'), "items.$index.quantity");
                if ($qty->isZero()) continue;
                $remaining = BigDecimal::of($quantities[$lineId]['remaining']);
                if ($qty->isGreaterThan($remaining)) {
                    if (empty($data['accept_surplus'])) $this->fail('replenishment.surplus_explicit');
                    app(PurchaseOrderService::class)->actor($actor, $shop, ['purchase_order_surplus']);
                    $this->reason($data);
                    $extra = $qty->minus($remaining);
                    $changes[$line->purchase_item_id] = (string) BigDecimal::of($line->quantity_ordered)->plus($extra);
                }
                if (!in_array($input['expiry_status'] ?? 'unknown', ['unknown','dated','not_applicable'], true)
                    || (($input['expiry_status'] ?? '') === 'dated' && empty($input['expires_on']))) $this->fail('replenishment.expiry_required');
                $lines[] = [
                    'purchase_item_id' => $line->purchase_item_id, 'quantity' => (string) $qty,
                    'expiry_status' => $input['expiry_status'] ?? 'unknown',
                    'expires_on' => $input['expires_on'] ?? null, 'batch_number' => $input['batch_number'] ?? null,
                ];
            }
            if (!$lines) $this->fail('replenishment.receipt_lines');
            $purchase = Purchase::whereKey($order->purchase_id)->lockForUpdate()->firstOrFail();
            if ($changes) {
                app(PurchaseService::class)->reviseOrderQuantities($purchase, $order->id, $changes, $actor, $shop, $data['reason'], 'po-surplus-'.hash('sha256', $data['operation_key']), false);
                foreach ($order->items as $line) {
                    if (!isset($changes[$line->purchase_item_id])) continue;
                    $quantity = BigDecimal::of($changes[$line->purchase_item_id]);
                    $cost = $line->purchaseItem()->firstOrFail()->source_unit_cost;
                    $line->forceFill(['quantity_ordered' => (string) $quantity,
                        'discount_amount' => (string) BigDecimal::of($line->unit_price)->minus($cost)->multipliedBy($quantity)])->save();
                }
            }
            $receipt = app(PurchaseService::class)->receive($purchase, $lines, PointOfSale::findOrFail($shop), $actor, 'po-receipt-'.hash('sha256', $data['operation_key']), $order->id);
            $purchase->refresh();
            $order->forceFill(['status' => $purchase->receipt_status, 'total_amount' => $purchase->grand_total, 'version' => $order->version + 1])->save();
            $this->event($order, 'receive', $data, $actor, $hash, $before, ['receipt_id' => $receipt->id, 'surplus' => $changes]);
            if ($order->status === 'received') app(OperationalNotifications::class)->record('purchase_order_received','po-received:'.$order->id,
                (int)$order->point_of_sale_id,(int)$order->id,['reference'=>$order->reference],(int)$order->user_id);
            return $order->fresh('items');
        }, 3);
    }

    public function finish(int $id, string $action, array $data, int $actor, int $shop): PurchaseOrder
    {
        if (!in_array($action, ['close', 'cancel'], true)) throw new \InvalidArgumentException('Unknown fulfilment action');
        return DB::transaction(function () use ($id, $action, $data, $actor, $shop) {
            [$order, $hash, $prior] = $this->start($id, $action, $data, $actor, $shop, ['purchase_order_cancel']);
            if ($prior) return $prior;
            $this->open($order, $data);
            $this->reason($data);
            $before = $order->toArray();
            $quantities = $this->quantities($order);
            $purchase = Purchase::whereKey($order->purchase_id)->lockForUpdate()->firstOrFail();
            if ($action === 'cancel') {
                foreach ($quantities as $qty) if (BigDecimal::of($qty['received'])->isPositive()) $this->fail('replenishment.cancel_received');
                app(PurchaseService::class)->cancel($purchase, $data['reason'], PointOfSale::findOrFail($shop), $actor, $order->id);
                $status = 'cancelled';
            } else {
                $changes = [];
                $hasRemainder = false;
                foreach ($order->items as $line) {
                    $qty = $quantities[$line->id];
                    if (BigDecimal::of($qty['remaining'])->isPositive()) $hasRemainder = true;
                    $changes[$line->purchase_item_id] = $qty['received'];
                    $line->forceFill(['closed_quantity' => $qty['remaining']])->save();
                }
                if (!$hasRemainder) $this->fail('replenishment.no_remainder');
                app(PurchaseService::class)->reviseOrderQuantities($purchase, $order->id, $changes, $actor, $shop, $data['reason'], 'po-close-'.hash('sha256', $data['operation_key']), true);
                $status = 'closed';
            }
            $purchase->refresh();
            $order->forceFill(['status' => $status, 'closed_at' => now('UTC'), 'total_amount' => $purchase->grand_total, 'version' => $order->version + 1])->save();
            $this->event($order, $action, $data, $actor, $hash, $before);
            return $order->fresh('items');
        }, 3);
    }

    private function start(int $id, string $action, array $data, int $actor, int $shop, array $permissions): array
    {
        User::whereKey($actor)->lockForUpdate()->firstOrFail();
        app(PurchaseOrderService::class)->actor($actor, $shop, $permissions);
        if (!isset($data['operation_key']) || strlen($data['operation_key']) < 8 || strlen($data['operation_key']) > 96) $this->fail('replenishment.key_conflict');
        $order = PurchaseOrder::whereKey($id)->where('point_of_sale_id', $shop)->lockForUpdate()->firstOrFail()->load('items');
        $hash = hash('sha256', json_encode([$action, $actor, $shop, $id, $data], JSON_THROW_ON_ERROR));
        $event = PurchaseOrderEvent::where('operation_key', $data['operation_key'])->lockForUpdate()->first();
        if ($event && !hash_equals($event->request_hash, $hash)) $this->fail('replenishment.key_conflict');
        return [$order, $hash, $event ? $order : null];
    }

    private function open(PurchaseOrder $order, array $data): void
    {
        if (!in_array($order->status, ['approved', 'issued', 'confirmed', 'partial'], true) || !$order->purchase_id
            || !isset($data['version']) || (int) $data['version'] !== $order->version) $this->fail('replenishment.stale_draft');
    }

    private function reason(array $data): void
    {
        if (!is_string($data['reason'] ?? null) || trim($data['reason']) === '' || mb_strlen($data['reason']) > 500) $this->fail('replenishment.reason_required');
    }

    private function event(PurchaseOrder $order, string $action, array $data, int $actor, string $hash, array $before, array $details = []): void
    {
        $after = $order->fresh()->load('items')->toArray();
        $after['fulfilment'] = $this->quantities($order->fresh('items'));
        $after['operation'] = $details;
        PurchaseOrderEvent::create(['purchase_order_id' => $order->id, 'user_id' => $actor, 'action' => $action,
            'operation_key' => $data['operation_key'], 'request_hash' => $hash, 'reason' => $data['reason'] ?? '',
            'before_values' => $before, 'after_values' => $after, 'occurred_at' => now('UTC')]);
        app(ReplenishmentAudit::class)->record('purchase_order.'.$action, $order->id, $order->point_of_sale_id, $before, $after, $data['reason'] ?? '', $actor);
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['purchase_order' => __($message)]);
    }
}
