<?php

namespace App\Services;

use App\Models\BatchStock;
use App\Models\OrderProduct;
use App\Models\OrderStockAllocation;
use App\Models\PointOfSale;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Support\MoneyDecimal;
use App\Support\QuantityDecimal;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Transactional stock journal and balance writer.
 *
 * Callers remain responsible for authorizing the acting user for the selected
 * shop. Every public write requires an explicit shop and an operation key.
 */
class StockService
{
    private const SALEABLE = 'saleable';
    private const UNSALEABLE = 'unsaleable';
    private const IN_TRANSIT = 'in_transit';
    private const UNALLOCATED_OPENING = 'unallocated_opening';

    public function increase(int $shopId, int $productId, mixed $quantity, array $options = []): StockMovement
    {
        return DB::transaction(function () use ($shopId, $productId, $quantity, $options) {
            $product = $this->lockProduct($productId);
            $this->assertActiveShop($shopId);
            $amount = $this->baseQuantity($product, $quantity);
            $type = $options['type'] ?? 'receipt';
            $this->assertType($type);
            $bucket = $options['bucket'] ?? self::SALEABLE;
            $this->assertBucket($bucket, [self::SALEABLE, self::UNSALEABLE, self::IN_TRANSIT, self::UNALLOCATED_OPENING]);
            if ($type === 'receipt' && !isset($options['batch_id']) && !isset($options['batch'])) {
                throw ValidationException::withMessages(['batch' => __('A receipt must create or reference a stock batch.')]);
            }
            if (($type === 'opening' && $bucket !== self::UNALLOCATED_OPENING)
                || ($bucket === self::UNALLOCATED_OPENING && $type !== 'opening')) {
                throw ValidationException::withMessages(['bucket' => __('Opening stock without provenance must remain unallocated until explicitly approved.')]);
            }
            $key = $this->operationKey($options['correlation_key'] ?? null);
            $line = $this->lineNumber($options['correlation_line'] ?? 1);
            $existing = StockMovement::query()->where('point_of_sale_id', $shopId)
                ->where('correlation_key', $key)->where('correlation_line', $line)->first();
            if ($existing) {
                $this->assertReplayMatches($existing, $productId, $amount, $type, $bucket, $options);
                $this->assertBatchReplayMatches($existing, $options['batch'] ?? null);
                return $existing;
            }

            $stock = $this->lockProductStock($shopId, $productId);
            $batch = $this->resolveBatch($product, $type, $options);
            $cost = $batch?->unit_cost;
            if (array_key_exists('unit_cost', $options)) {
                $requestedCost = $options['unit_cost'] === null ? null : (string) MoneyDecimal::parse($options['unit_cost'], 'unit_cost');
                if ($batch && (($batch->unit_cost === null && $requestedCost !== null)
                    || ($batch->unit_cost !== null && ($requestedCost === null
                        || BigDecimal::of($requestedCost)->compareTo($batch->unit_cost) !== 0)))) {
                    throw ValidationException::withMessages(['unit_cost' => __('A transfer must preserve the batch cost.')]);
                }
                if (!$batch) {
                    $cost = $requestedCost;
                }
            }
            if ($batch && (int) $batch->product_id !== $productId) {
                throw ValidationException::withMessages(['product' => __('The stock batch does not belong to this product.')]);
            }

            if ($batch && $bucket === self::UNALLOCATED_OPENING) {
                throw ValidationException::withMessages(['bucket' => __('Unallocated opening stock cannot carry a batch.')]);
            }
            if ($batch && in_array($bucket, [self::SALEABLE, self::UNSALEABLE], true)) {
                $batchStock = $this->lockBatchStock($shopId, $batch->id);
                $batchField = $bucket.'_quantity';
                $batchStock->setAttribute($batchField, $this->add($batchStock->getAttribute($batchField), $amount));
                $batchStock->save();
            }

            $stockField = $bucket.'_quantity';
            $stock->setAttribute($stockField, $this->add($stock->getAttribute($stockField), $amount));
            $stock->save();

            return $this->writeMovement([
                'point_of_sale_id' => $shopId,
                'product_id' => $productId,
                'product_batch_id' => $batch?->id,
                'bucket' => $bucket,
                'quantity_delta' => (string) $amount,
                'type' => $type,
                'occurred_at' => $options['occurred_at'] ?? now('Africa/Douala'),
                'user_id' => $options['user_id'] ?? null,
                'unit_cost' => $cost,
                'correlation_key' => $key,
                'correlation_line' => $line,
                'order_product_id' => null,
                ...array_intersect_key($options, ['purchase_receipt_item_id' => true]),
                'conversion_run_id' => $options['conversion_run_id'] ?? null,
                'reversal_of_id' => $options['reversal_of_id'] ?? null,
                'reason' => $options['reason'] ?? null,
            ]);
        }, 3);
    }

    /**
     * Remove base units using FEFO for dated batches, then FIFO for batches
     * without an expiry. Approved lotless stock is consumed after batch stock.
     * Unknown opening stock is never considered available.
     */
    public function decrease(int $shopId, int $productId, mixed $quantity, array $options = []): Collection
    {
        return DB::transaction(function () use ($shopId, $productId, $quantity, $options) {
            $product = $this->lockProduct($productId);
            $this->assertActiveShop($shopId);
            $amount = $this->baseQuantity($product, $quantity);
            $type = $options['type'] ?? 'sale';
            $this->assertType($type);
            $bucket = $options['bucket'] ?? self::SALEABLE;
            $this->assertBucket($bucket, [self::SALEABLE, self::UNSALEABLE]);
            $key = $this->operationKey($options['correlation_key'] ?? null);
            $orderProductId = isset($options['order_product_id']) ? (int) $options['order_product_id'] : null;
            if (($orderProductId || $type === 'sale') && $bucket !== self::SALEABLE) {
                throw ValidationException::withMessages(['bucket' => __('Sales can only allocate from saleable stock.')]);
            }
            if ($orderProductId) {
                $orderProduct = OrderProduct::query()->whereKey($orderProductId)->lockForUpdate()->firstOrFail();
                if ((int) $orderProduct->product_id !== $productId) {
                    throw ValidationException::withMessages(['order_product_id' => __('The sale line does not belong to this product.')]);
                }
            }
            $existing = StockMovement::query()->where('point_of_sale_id', $shopId)
                ->where('correlation_key', $key)->where('type', $type)->orderBy('correlation_line')->get();
            if ($existing->isNotEmpty()) {
                $this->assertCollectionReplayMatches($existing, $productId, $amount, $type, $bucket, $orderProductId, $options['reason'] ?? null);
                return $existing;
            }

            $stock = $this->lockProductStock($shopId, $productId);
            $today = now('Africa/Douala')->toDateString();
            $includeBlockedBatches = in_array($type, ['adjustment', 'loss', 'inventory_adjustment'], true);
            $stockField = $bucket.'_quantity';
            $available = BigDecimal::of($stock->getAttribute($stockField));
            if ($bucket === self::SALEABLE && !$includeBlockedBatches) {
                $available = $available->minus($this->expiredBatchTotal($shopId, $productId, $today))
                    ->minus($this->unknownExpiryTotal($shopId, $productId));
            }
            if ($available->isLessThan($amount)) {
                throw ValidationException::withMessages(['quantity' => __('Insufficient available stock.')]);
            }

            $remaining = $amount;
            $created = new Collection();
            $line = (int) ($options['correlation_line'] ?? 1);
            $batches = $this->lockAvailableBatches($shopId, $productId, $today, $bucket, $includeBlockedBatches);
            $initialBatchQuantity = $batches->reduce(
                fn (BigDecimal $sum, BatchStock $batchStock) => $sum->plus($batchStock->getAttribute($stockField)),
                BigDecimal::zero()
            );
            foreach ($batches as $batchStock) {
                if ($remaining->isZero()) {
                    break;
                }
                $onHand = BigDecimal::of($batchStock->getAttribute($stockField));
                if ($onHand->isZero() || $onHand->isNegative()) {
                    continue;
                }
                $take = $onHand->isLessThan($remaining) ? $onHand : $remaining;
                $batchStock->setAttribute($stockField, (string) $onHand->minus($take)->toScale(6));
                $batchStock->save();
                $remaining = $remaining->minus($take);
                $movement = $this->writeMovement([
                    'point_of_sale_id' => $shopId,
                    'product_id' => $productId,
                    'product_batch_id' => $batchStock->product_batch_id,
                    'bucket' => $bucket,
                    'quantity_delta' => (string) $take->negated()->toScale(6),
                    'type' => $type,
                    'occurred_at' => $options['occurred_at'] ?? now('Africa/Douala'),
                    'user_id' => $options['user_id'] ?? null,
                    'unit_cost' => $batchStock->batch?->unit_cost,
                    'correlation_key' => $key,
                    'correlation_line' => $line++,
                    'order_product_id' => $orderProductId,
                    'conversion_run_id' => $options['conversion_run_id'] ?? null,
                    'reversal_of_id' => null,
                    'reason' => $options['reason'] ?? null,
                ]);
                if ($bucket === self::SALEABLE) {
                    $this->writeAllocation($movement, $batchStock->batch, $orderProductId, $take);
                }
                $created->push($movement);
            }

            if ($remaining->isPositive()) {
                $lotlessAvailable = BigDecimal::of($stock->getAttribute($stockField))->minus($initialBatchQuantity);
                if ($bucket === self::SALEABLE && !$includeBlockedBatches) {
                    $lotlessAvailable = $lotlessAvailable
                        ->minus($this->expiredBatchTotal($shopId, $productId, $today))
                        ->minus($this->unknownExpiryTotal($shopId, $productId));
                }
                if ($lotlessAvailable->isLessThan($remaining)) {
                    throw ValidationException::withMessages(['quantity' => __('Stock balance does not match its available batches.')]);
                }
                $movement = $this->writeMovement([
                    'point_of_sale_id' => $shopId,
                    'product_id' => $productId,
                    'product_batch_id' => null,
                    'bucket' => $bucket,
                    'quantity_delta' => (string) $remaining->negated()->toScale(6),
                    'type' => $type,
                    'occurred_at' => $options['occurred_at'] ?? now('Africa/Douala'),
                    'user_id' => $options['user_id'] ?? null,
                    'unit_cost' => null,
                    'correlation_key' => $key,
                    'correlation_line' => $line,
                    'order_product_id' => $orderProductId,
                    'conversion_run_id' => $options['conversion_run_id'] ?? null,
                    'reversal_of_id' => null,
                    'reason' => $options['reason'] ?? null,
                ]);
                if ($bucket === self::SALEABLE) {
                    $this->writeAllocation($movement, null, $orderProductId, $remaining);
                }
                $created->push($movement);
            }

            $stock->setAttribute($stockField, $this->add($stock->getAttribute($stockField), (string) $amount->negated()->toScale(6)));
            $stock->save();
            return $created;
        }, 3);
    }

    /**
     * Execute work while holding the product and batch locks. This is a
     * transaction-scoped availability guard; it never persists a cart hold.
     */
    public function reserve(int $shopId, int $productId, mixed $quantity, Closure $operation): mixed
    {
        return DB::transaction(function () use ($shopId, $productId, $quantity, $operation) {
            $product = $this->lockProduct($productId);
            $this->assertActiveShop($shopId);
            $amount = $this->baseQuantity($product, $quantity);
            $stock = $this->lockProductStock($shopId, $productId);
            if (BigDecimal::of($stock->saleable_quantity)
                ->minus($this->expiredBatchTotal($shopId, $productId, now('Africa/Douala')->toDateString()))
                ->minus($this->unknownExpiryTotal($shopId, $productId))->isLessThan($amount)) {
                throw ValidationException::withMessages(['quantity' => __('Insufficient available stock.')]);
            }
            $batches = $this->lockAvailableBatches($shopId, $productId, now('Africa/Douala')->toDateString());
            return $operation($stock, $batches);
        }, 3);
    }

    /**
     * Internal atomic relocation primitive, preserving batch identity.
     * Physical dispatch/receipt must use StockTransferService and its transit
     * documents; application screens must not bypass that lifecycle.
     */
    public function transfer(int $sourceShopId, int $destinationShopId, int $productId, mixed $quantity, string $correlationKey, array $options = []): array
    {
        if ($sourceShopId === $destinationShopId) {
            throw ValidationException::withMessages(['point_of_sale_id' => __('Choose two different shops for a transfer.')]);
        }

        return DB::transaction(function () use ($sourceShopId, $destinationShopId, $productId, $quantity, $correlationKey, $options) {
            $this->lockProduct($productId);
            $this->assertActiveShop($sourceShopId);
            $this->assertActiveShop($destinationShopId);
            $shopIds = [$sourceShopId, $destinationShopId];
            sort($shopIds, SORT_NUMERIC);
            foreach ($shopIds as $shopId) {
                $this->lockProductStock($shopId, $productId);
            }

            $key = $this->operationKey($correlationKey);
            $existingOut = StockMovement::query()->where('point_of_sale_id', $sourceShopId)
                ->where('correlation_key', $key)->where('type', 'transfer_out')->orderBy('correlation_line')->get();
            $existingIn = StockMovement::query()->where('point_of_sale_id', $destinationShopId)
                ->where('correlation_key', $key)->where('type', 'transfer_in')->orderBy('correlation_line')->get();
            if ($existingOut->isNotEmpty() || $existingIn->isNotEmpty()) {
                $amount = $this->baseQuantity(Product::findOrFail($productId), $quantity);
                $bucket = $options['bucket'] ?? self::SALEABLE;
                $this->assertCollectionReplayMatches($existingOut, $productId, $amount, 'transfer_out', $bucket, null, $options['reason'] ?? null);
                $this->assertCollectionReplayMatches($existingIn, $productId, $amount, 'transfer_in', $bucket, null, $options['reason'] ?? null, false);
                return ['out' => $existingOut, 'in' => $existingIn];
            }

            $out = $this->decrease($sourceShopId, $productId, $quantity, [
                ...$options, 'type' => 'transfer_out', 'correlation_key' => $key,
            ]);
            $in = new Collection();
            foreach ($out as $movement) {
                $in->push($this->increase($destinationShopId, $productId, ltrim((string) $movement->quantity_delta, '-'), [
                    ...$options,
                    'type' => 'transfer_in',
                    'bucket' => $movement->bucket,
                    'correlation_key' => $key,
                    'correlation_line' => $movement->correlation_line,
                    'batch_id' => $movement->product_batch_id,
                    'unit_cost' => $movement->unit_cost,
                ]));
            }
            return ['out' => $out, 'in' => $in];
        }, 3);
    }

    /** Apply a signed, reasoned correction to the available stock balance. */
    public function adjust(int $shopId, int $productId, mixed $quantityDelta, string $reason, string $correlationKey, array $options = []): Collection|StockMovement
    {
        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => __('A reason is required for a stock adjustment.')]);
        }
        $delta = $this->signedQuantityDelta($quantityDelta);
        $type = $options['type'] ?? 'adjustment';
        $options['type'] = $type;
        $options['correlation_key'] = $correlationKey;
        $options['reason'] = trim($reason);

        if ($delta->isPositive()) {
            return $this->increase($shopId, $productId, (string) $delta, [
                ...$options, 'bucket' => $options['bucket'] ?? self::SALEABLE,
            ]);
        }

        return $this->decrease($shopId, $productId, (string) $delta->abs(), [
            ...$options, 'type' => $type,
        ]);
    }

    /** Compensate an untouched receipt in its original batch, including blocked expiry. */
    public function reverseReceipt(int $shopId, int $sourceId, string $reason, string $key, int $userId): StockMovement
    {
        return DB::transaction(function () use ($shopId, $sourceId, $reason, $key, $userId) {
            if (trim($reason) === '') throw ValidationException::withMessages(['reason'=>__('A reason is required for a stock adjustment.')]);
            $key = $this->operationKey($key);
            $source = StockMovement::query()->findOrFail($sourceId);
            $this->lockProduct((int)$source->product_id);
            $this->assertActiveShop($shopId);
            $source = StockMovement::query()->whereKey($sourceId)->lockForUpdate()->firstOrFail();
            if ((int)$source->point_of_sale_id !== $shopId || $source->type !== 'receipt' || !$source->product_batch_id || !$source->purchase_receipt_item_id)
                throw ValidationException::withMessages(['purchase'=>__('Only a traced purchase receipt can be reversed.')]);
            $this->assertBucket($source->bucket, [self::SALEABLE,self::UNSALEABLE]);
            $existing = StockMovement::query()->where('reversal_of_id', $sourceId)->first();
            if ($existing) {
                if ($existing->correlation_key !== $key || $existing->reason !== trim($reason))
                    throw ValidationException::withMessages(['purchase'=>__('This receipt was already reversed with different data.')]);
                return $existing;
            }
            if (StockMovement::query()->where('product_batch_id', $source->product_batch_id)->whereKeyNot($sourceId)->exists())
                throw ValidationException::withMessages(['purchase'=>__('A receipt whose lot has been used or transferred cannot be cancelled.')]);
            $amount = BigDecimal::of($source->quantity_delta);
            if (!$amount->isPositive()) throw ValidationException::withMessages(['purchase'=>__('Invalid receipt quantity.')]);
            $stock = $this->lockProductStock($shopId, (int)$source->product_id);
            $batch = $this->lockBatchStock($shopId, (int)$source->product_batch_id);
            $field = $source->bucket.'_quantity';
            if (!BigDecimal::of($batch->getAttribute($field))->isEqualTo($amount) || BigDecimal::of($stock->getAttribute($field))->isLessThan($amount))
                throw ValidationException::withMessages(['purchase'=>__('The receipt lot no longer matches its opening quantity.')]);
            $batch->setAttribute($field, '0.000000'); $batch->save();
            $stock->setAttribute($field, (string)BigDecimal::of($stock->getAttribute($field))->minus($amount)->toScale(6)); $stock->save();
            return $this->writeMovement([
                'point_of_sale_id'=>$shopId, 'product_id'=>$source->product_id, 'product_batch_id'=>$source->product_batch_id,
                'bucket'=>$source->bucket, 'quantity_delta'=>(string)$amount->negated()->toScale(6), 'type'=>'adjustment',
                'occurred_at'=>now('Africa/Douala'), 'user_id'=>$userId, 'unit_cost'=>$source->unit_cost,
                'correlation_key'=>$key, 'correlation_line'=>1, 'purchase_receipt_item_id'=>$source->purchase_receipt_item_id,
                'reversal_of_id'=>$source->id, 'reason'=>trim($reason),
            ]);
        }, 3);
    }

    public function getStock(int $shopId, int $productId): array
    {
        $stock = ProductStock::query()->where('point_of_sale_id', $shopId)->where('product_id', $productId)->first();
        $expired = $stock
            ? $this->expiredBatchTotal($shopId, $productId, now('Africa/Douala')->toDateString())
            : BigDecimal::zero();
        $unknownExpiry = $stock ? $this->unknownExpiryTotal($shopId, $productId) : BigDecimal::zero();
        $saleable = BigDecimal::of($stock?->saleable_quantity ?? '0.000000');
        return [
            'saleable' => (string) $saleable->toScale(6),
            'unsaleable' => (string) BigDecimal::of($stock?->unsaleable_quantity ?? '0.000000')->plus($expired)->toScale(6),
            'in_transit' => $stock?->in_transit_quantity ?? '0.000000',
            'unallocated_opening' => $stock?->unallocated_opening_quantity ?? '0.000000',
            'blocked_expiry' => (string) $unknownExpiry->toScale(6),
            'available' => (string) ($saleable->minus($expired)->minus($unknownExpiry)->isNegative()
                ? BigDecimal::zero() : $saleable->minus($expired)->minus($unknownExpiry))->toScale(6),
        ];
    }

    public function available(int $shopId, int $productId): string
    {
        $stock = ProductStock::query()->where('point_of_sale_id', $shopId)->where('product_id', $productId)->first();
        if (!$stock) {
            return '0.000000';
        }
        $available = BigDecimal::of($stock->saleable_quantity)
            ->minus($this->expiredBatchTotal($shopId, $productId, now('Africa/Douala')->toDateString()))
            ->minus($this->unknownExpiryTotal($shopId, $productId));
        return (string) ($available->isNegative() ? BigDecimal::zero() : $available)->toScale(6);
    }

    public function inTransit(int $shopId, int $productId): string
    {
        return $this->getStock($shopId, $productId)['in_transit'];
    }

    public function unsellable(int $shopId, int $productId): string
    {
        return $this->getStock($shopId, $productId)['unsaleable'];
    }

    /**
     * Target one physical bucket/lot. Unlike FEFO consumption, an inventory or
     * transit settlement must never silently take units from another lot.
     * Positive unallocated openings are deliberately not supported here.
     */
    public function correctBucket(int $shopId, int $productId, ?int $batchId, string $bucket, string $delta, string $type, string $key, string $reason, int $userId, int $line = 1): StockMovement
    {
        return DB::transaction(function () use ($shopId,$productId,$batchId,$bucket,$delta,$type,$key,$reason,$userId,$line) {
            $product = $this->lockProduct($productId); $this->assertActiveShop($shopId);
            $this->assertBucket($bucket,[self::SALEABLE,self::UNSALEABLE,self::IN_TRANSIT,self::UNALLOCATED_OPENING]);
            $this->assertType($type);
            if (!in_array($type,['inventory_adjustment','opening_approved','transfer_in','transfer_out','loss'],true) || trim($reason)==='')
                throw ValidationException::withMessages(['reason'=>__('A reason is required for a stock adjustment.')]);
            $amount = $this->signedQuantityDelta($delta);
            $this->baseQuantity($product,(string)$amount->abs());
            if ($bucket===self::UNALLOCATED_OPENING && ($batchId!==null || $amount->isPositive()))
                throw ValidationException::withMessages(['bucket'=>__('Opening stock without provenance must remain unallocated until explicitly approved.')]);
            if ($bucket===self::IN_TRANSIT && !$batchId)
                throw ValidationException::withMessages(['batch'=>__('Transit requires a traced batch.')]);
            if ($type==='inventory_adjustment' && $bucket===self::IN_TRANSIT)
                throw ValidationException::withMessages(['bucket'=>__('Settle transit through its transfer document.')]);
            $key=$this->operationKey($key); $line=$this->lineNumber($line);
            $existing=StockMovement::where('point_of_sale_id',$shopId)->where('correlation_key',$key)->where('correlation_line',$line)->first();
            if ($existing) {
                $this->assertReplayMatches($existing,$productId,$amount,$type,$bucket,['batch_id'=>$batchId,'reason'=>trim($reason)]);
                return $existing;
            }
            $batch=$batchId ? ProductBatch::whereKey($batchId)->lockForUpdate()->firstOrFail() : null;
            if ($batch && (int)$batch->product_id!==$productId) throw ValidationException::withMessages(['batch'=>__('The stock batch does not belong to this product.')]);
            $stock=$this->lockProductStock($shopId,$productId); $field=$bucket.'_quantity';
            if ($batch && in_array($bucket,[self::SALEABLE,self::UNSALEABLE],true)) {
                $lot=$this->lockBatchStock($shopId,$batchId);
                $lot->setAttribute($field,$this->add($lot->getAttribute($field),(string)$amount)); $lot->save();
            } elseif ($batch && $bucket===self::IN_TRANSIT) {
                $balance=StockMovement::where('point_of_sale_id',$shopId)->where('product_batch_id',$batchId)->where('bucket',$bucket)->sum('quantity_delta');
                if (BigDecimal::of($balance)->plus($amount)->isNegative()) throw ValidationException::withMessages(['quantity'=>__('Insufficient transit stock for this lot.')]);
            } elseif (in_array($bucket,[self::SALEABLE,self::UNSALEABLE],true)) {
                // Never create a saleable lot without evidence or consume someone else's lot.
                if ($amount->isPositive()) throw ValidationException::withMessages(['batch'=>__('Positive physical corrections require a documented batch.')]);
                $assigned=BatchStock::where('point_of_sale_id',$shopId)->whereHas('batch',fn($q)=>$q->where('product_id',$productId))->sum($field);
                if (BigDecimal::of($stock->getAttribute($field))->minus($assigned)->plus($amount)->isNegative())
                    throw ValidationException::withMessages(['batch'=>__('Insufficient lotless stock.')]);
            }
            $stock->setAttribute($field,$this->add($stock->getAttribute($field),(string)$amount)); $stock->save();
            return $this->writeMovement([
                'point_of_sale_id'=>$shopId,'product_id'=>$productId,'product_batch_id'=>$batchId,'bucket'=>$bucket,
                'quantity_delta'=>(string)$amount->toScale(6),'type'=>$type,'occurred_at'=>now('Africa/Douala'),
                'user_id'=>$userId,'unit_cost'=>$batch?->unit_cost,'correlation_key'=>$key,'correlation_line'=>$line,'reason'=>trim($reason),
            ]);
        },3);
    }

    public function approveOpening(int $shopId, int $productId, array $data, int $userId): object
    {
        return DB::transaction(function () use ($shopId,$productId,$data,$userId) {
            $actor=\App\Models\User::findOrFail($userId);
            abort_unless($actor->can('stock_opening_approve') && PointOfSale::accessibleBy($actor)->whereKey($shopId)->exists(),403);
            \Illuminate\Support\Facades\Validator::make($data,[
                'quantity'=>'required|string','unit_cost'=>'required|string','batch_number'=>'required|string|max:255',
                'expiry_status'=>'required|in:dated,not_applicable','expires_on'=>'nullable|date_format:Y-m-d',
                'reason'=>'required|string|max:5000','evidence'=>'required|string|max:5000','currency_code'=>'required|in:XAF,BDT','operation_key'=>'required|string|max:64',
            ])->validate();
            $product=$this->lockProduct($productId); $this->assertActiveShop($shopId);
            $key=$this->operationKey($data['operation_key']);
            $hash=hash('sha256',json_encode([$shopId,$productId,$userId,$data],JSON_THROW_ON_ERROR));
            $existing=DB::table('stock_opening_approvals')->where('operation_key',$key)->first();
            if ($existing) {
                if (!hash_equals($existing->request_hash,$hash)) throw ValidationException::withMessages(['operation_key'=>__('This operation key was already used with different data.')]);
                return $existing;
            }
            $amount=$this->baseQuantity($product,$data['quantity']);
            if (trim($data['reason'] ?? '')==='' || trim($data['evidence'] ?? '')==='' || trim($data['batch_number'] ?? '')==='')
                throw ValidationException::withMessages(['evidence'=>__('Provide a reason, lot identifier and documentary evidence.')]);
            $cost=(string)MoneyDecimal::parse($data['unit_cost'],'unit_cost');
            if (!in_array($data['currency_code'] ?? '',['XAF','BDT'],true)) throw ValidationException::withMessages(['currency_code'=>__('Specify the evidenced cost currency.')]);
            if (!in_array($data['expiry_status'] ?? '',['dated','not_applicable'],true))
                throw ValidationException::withMessages(['expiry_status'=>__('Unknown expiry cannot be approved.')]);
            $batch=$this->resolveBatch($product,'opening_approved',['batch'=>[
                'batch_number'=>$data['batch_number'],'unit_cost'=>$cost,'currency_code'=>$data['currency_code'],
                'expiry_status'=>$data['expiry_status'],'expires_on'=>$data['expires_on'] ?? null,'provenance'=>'documented_opening',
                // Opening cutover is a known FIFO boundary, not an invented
                // supplier receipt date. Late approval must not age old stock
                // behind purchases made after that cutover.
                'received_at'=>StockMovement::where('point_of_sale_id',$shopId)->where('product_id',$productId)
                    ->where('type','opening')->orderBy('id')->first()?->occurred_at ?? now('Africa/Douala'),
            ]]);
            $this->correctBucket($shopId,$productId,null,self::UNALLOCATED_OPENING,(string)$amount->negated(),'opening_approved',$key,trim($data['reason']),$userId,1);
            $this->increase($shopId,$productId,(string)$amount,[
                'type'=>'opening_approved','batch_id'=>$batch->id,'correlation_key'=>$key,'correlation_line'=>2,'user_id'=>$userId,'reason'=>trim($data['reason']),
            ]);
            $id=DB::table('stock_opening_approvals')->insertGetId([
                'point_of_sale_id'=>$shopId,'product_id'=>$productId,'user_id'=>$userId,'product_batch_id'=>$batch->id,
                'quantity'=>(string)$amount,'unit_cost'=>$cost,'currency_code'=>$data['currency_code'],
                'operation_key'=>$key,'request_hash'=>$hash,'reason'=>$data['reason'],'evidence'=>$data['evidence'],
                'approved_at'=>now('Africa/Douala'),'created_at'=>now(),'updated_at'=>now(),
            ]);
            return DB::table('stock_opening_approvals')->find($id);
        },3);
    }

    private function resolveBatch(Product $product, string $type, array $options): ?ProductBatch
    {
        if (isset($options['batch_id'])) {
            return ProductBatch::query()->whereKey((int) $options['batch_id'])->lockForUpdate()->firstOrFail();
        }
        if (isset($options['batch'])) {
            $data = $options['batch'];
            $expiryStatus = $data['expiry_status'] ?? null;
            if (!in_array($expiryStatus, ['dated', 'not_applicable', 'unknown'], true)
                || ($expiryStatus === 'dated' && empty($data['expires_on']))
                || ($expiryStatus !== 'dated' && !empty($data['expires_on']))) {
                throw ValidationException::withMessages(['expiry_status' => __('Classify the batch expiry as dated, not applicable, or unknown; a dated batch needs an expiration date.')]);
            }
            if ($expiryStatus !== 'unknown' && ProductBatch::query()->where('product_id', $product->id)
                ->whereIn('expiry_status', ['dated', 'not_applicable'])
                ->where('expiry_status', '<>', $expiryStatus)->exists()) {
                throw ValidationException::withMessages(['expiry_status' => __('Known batches for one product must use the same expiry classification.')]);
            }
            $cost = array_key_exists('unit_cost', $data) && $data['unit_cost'] !== null
                ? (string) MoneyDecimal::parse($data['unit_cost'], 'unit_cost') : null;
            return ProductBatch::query()->create([
                'product_id' => $product->id,
                'batch_number' => isset($data['batch_number']) ? trim((string) $data['batch_number']) : null,
                'expiry_status' => $expiryStatus,
                'expires_on' => $data['expires_on'] ?? null,
                'received_at' => $data['received_at'] ?? now('Africa/Douala'),
                'unit_cost' => $cost,
                'purchase_receipt_item_id' => $data['purchase_receipt_item_id'] ?? null,
                ...array_intersect_key($data, ['currency_code'=>true]),
                'provenance' => $data['provenance'] ?? $type,
            ]);
        }
        if ($type === 'receipt') {
            throw ValidationException::withMessages(['batch' => __('A receipt must create or reference a stock batch.')]);
        }
        return null;
    }

    private function lockProduct(int $productId): Product
    {
        $product = Product::query()->whereKey($productId)->lockForUpdate()->firstOrFail();
        if ($product->allows_fractional === null) {
            throw ValidationException::withMessages(['product' => __('Configure whether this product allows fractional quantities before using stock.')]);
        }
        return $product;
    }

    private function assertActiveShop(int $shopId): void
    {
        if (!PointOfSale::query()->whereKey($shopId)->where('is_active', true)->exists()) {
            throw ValidationException::withMessages(['point_of_sale_id' => __('Choose an active shop.')]);
        }
    }

    private function lockProductStock(int $shopId, int $productId): ProductStock
    {
        $now = now();
        DB::table('product_stock')->insertOrIgnore([
            'point_of_sale_id' => $shopId,
            'product_id' => $productId,
            'saleable_quantity' => '0.000000',
            'unsaleable_quantity' => '0.000000',
            'in_transit_quantity' => '0.000000',
            'unallocated_opening_quantity' => '0.000000',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        return ProductStock::query()->where('point_of_sale_id', $shopId)->where('product_id', $productId)
            ->lockForUpdate()->firstOrFail();
    }

    private function lockBatchStock(int $shopId, int $batchId): BatchStock
    {
        $now = now();
        DB::table('batch_stock')->insertOrIgnore([
            'point_of_sale_id' => $shopId,
            'product_batch_id' => $batchId,
            'saleable_quantity' => '0.000000',
            'unsaleable_quantity' => '0.000000',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        return BatchStock::query()->where('point_of_sale_id', $shopId)->where('product_batch_id', $batchId)
            ->lockForUpdate()->firstOrFail();
    }

    private function lockAvailableBatches(int $shopId, int $productId, string $today, string $bucket = self::SALEABLE, bool $includeBlocked = false): Collection
    {
        $query = BatchStock::query()->select('batch_stock.*')
            ->join('product_batches', 'product_batches.id', '=', 'batch_stock.product_batch_id')
            ->where('batch_stock.point_of_sale_id', $shopId)
            ->where('product_batches.product_id', $productId)
            ->where('batch_stock.'.$bucket.'_quantity', '>', 0);
        if ($bucket === self::SALEABLE && !$includeBlocked) {
            $query->where('product_batches.expiry_status', '<>', 'unknown')
                ->where(fn ($q) => $q->where('product_batches.expiry_status', '<>', 'dated')->orWhere('product_batches.expires_on', '>=', $today))
                // Dated batches are FEFO; explicitly non-expiring batches follow FIFO.
                ->orderByRaw("CASE WHEN product_batches.expiry_status = 'dated' THEN 0 ELSE 1 END")
                ->orderBy('product_batches.expires_on');
        }
        return $query->orderBy('product_batches.received_at')->orderBy('product_batches.id')
            ->lockForUpdate()->with('batch')->get();
    }

    private function expiredBatchTotal(int $shopId, int $productId, string $today): BigDecimal
    {
        $total = BatchStock::query()->join('product_batches', 'product_batches.id', '=', 'batch_stock.product_batch_id')
            ->where('batch_stock.point_of_sale_id', $shopId)->where('product_batches.product_id', $productId)
            ->where('product_batches.expiry_status', 'dated')->where('product_batches.expires_on', '<', $today)
            ->sum('batch_stock.saleable_quantity');
        return BigDecimal::of((string) ($total ?? '0'));
    }

    private function unknownExpiryTotal(int $shopId, int $productId): BigDecimal
    {
        $total = BatchStock::query()->join('product_batches', 'product_batches.id', '=', 'batch_stock.product_batch_id')
            ->where('batch_stock.point_of_sale_id', $shopId)->where('product_batches.product_id', $productId)
            ->where('product_batches.expiry_status', 'unknown')->sum('batch_stock.saleable_quantity');
        return BigDecimal::of((string) ($total ?? '0'));
    }

    private function writeMovement(array $data): StockMovement
    {
        $movement = StockMovement::query()->create($data);
        return $movement->load('batch');
    }

    private function writeAllocation(StockMovement $movement, ?ProductBatch $batch, ?int $orderProductId, BigDecimal $quantity): void
    {
        if (!$orderProductId) {
            return;
        }
        $unitCost = $batch?->unit_cost;
        $totalCost = $unitCost === null ? null : (string) MoneyDecimal::rounded(
            BigDecimal::of($unitCost)->multipliedBy($quantity)->toScale(6, RoundingMode::HalfUp)
        );
        OrderStockAllocation::query()->create([
            'order_product_id' => $orderProductId,
            'stock_movement_id' => $movement->id,
            'product_batch_id' => $batch?->id,
            'base_quantity' => (string) $quantity->toScale(6),
            'unit_cost' => $unitCost,
            'total_cost' => $totalCost,
            'provenance' => $batch ? 'batch' : 'approved_opening',
        ]);
    }

    private function assertReplayMatches(StockMovement $existing, int $productId, BigDecimal $amount, string $type, string $bucket, array $options): void
    {
        if ((int) $existing->product_id !== $productId || $existing->type !== $type || $existing->bucket !== $bucket
            || BigDecimal::of($existing->quantity_delta)->compareTo($amount) !== 0
            || (array_key_exists('batch_id', $options) && (int) ($existing->product_batch_id ?? 0) !== (int) ($options['batch_id'] ?? 0))
            || ($existing->reason ?? null) !== ($options['reason'] ?? null)
            || (isset($options['purchase_receipt_item_id']) && (int)$existing->purchase_receipt_item_id !== (int)$options['purchase_receipt_item_id'])) {
            throw ValidationException::withMessages(['correlation_key' => __('This stock operation key was already used for different values.')]);
        }
        if (array_key_exists('unit_cost', $options)) {
            $requested = $options['unit_cost'] === null ? null : (string) MoneyDecimal::parse($options['unit_cost'], 'unit_cost');
            if (($existing->unit_cost === null) !== ($requested === null)
                || ($requested !== null && BigDecimal::of($existing->unit_cost)->compareTo($requested) !== 0)) {
                throw ValidationException::withMessages(['correlation_key' => __('This stock operation key was already used for different values.')]);
            }
        }
    }

    private function assertBatchReplayMatches(StockMovement $existing, ?array $batchData): void
    {
        if ($batchData === null) {
            return;
        }
        $batch = $existing->batch;
        if (!$batch || (array_key_exists('batch_number', $batchData)
                && ($batch->batch_number ?? null) !== (isset($batchData['batch_number']) ? trim((string) $batchData['batch_number']) : null))
            || (array_key_exists('expiry_status', $batchData) && $batch->expiry_status !== $batchData['expiry_status'])
            || (array_key_exists('expires_on', $batchData)
                && (string) ($batch->expires_on?->toDateString() ?? '') !== (string) ($batchData['expires_on'] ?? ''))
            || (array_key_exists('unit_cost', $batchData)
                && (($batch->unit_cost === null) !== ($batchData['unit_cost'] === null)
                    || ($batchData['unit_cost'] !== null
                        && BigDecimal::of($batch->unit_cost)->compareTo((string) MoneyDecimal::parse($batchData['unit_cost'], 'unit_cost')) !== 0)))
            || (array_key_exists('currency_code',$batchData) && $batch->currency_code !== $batchData['currency_code'])
            || (array_key_exists('provenance', $batchData) && $batch->provenance !== $batchData['provenance'])) {
            throw ValidationException::withMessages(['correlation_key' => __('This stock operation key was already used for a different batch.')]);
        }
    }

    private function assertCollectionReplayMatches(Collection $existing, int $productId, BigDecimal $amount, string $type, string $bucket, ?int $orderProductId, ?string $reason = null, bool $negative = true): void
    {
        if ($existing->isEmpty() || $existing->contains(fn (StockMovement $movement) =>
            (int) $movement->product_id !== $productId || $movement->type !== $type || $movement->bucket !== $bucket
            || BigDecimal::of($movement->quantity_delta)->isNegative() !== $negative
            || (int) ($movement->order_product_id ?? 0) !== (int) ($orderProductId ?? 0)
            || ($movement->reason ?? null) !== $reason)) {
            throw ValidationException::withMessages(['correlation_key' => __('This stock operation key was already used for different values.')]);
        }
        $total = $existing->reduce(fn (BigDecimal $sum, StockMovement $movement) => $sum->plus(BigDecimal::of($movement->quantity_delta)->abs()), BigDecimal::zero());
        if ($total->compareTo($amount) !== 0) {
            throw ValidationException::withMessages(['correlation_key' => __('This stock operation key was already used for different values.')]);
        }
    }

    private function baseQuantity(Product $product, mixed $quantity): BigDecimal
    {
        $amount = QuantityDecimal::parse($quantity, 'quantity', true);
        if (!$product->allows_fractional) {
            try {
                $amount->toScale(0);
            } catch (\Brick\Math\Exception\RoundingNecessaryException) {
                throw ValidationException::withMessages(['quantity' => __('This product requires whole quantities.')]);
            }
        }
        return $amount->toScale(6);
    }

    private function signedQuantityDelta(mixed $value): BigDecimal
    {
        if ((!is_string($value) && !is_int($value))
            || !preg_match('/\A-?(?:0|[1-9][0-9]{0,13})(?:\.[0-9]{1,6})?\z/D', (string) $value)) {
            throw ValidationException::withMessages(['quantity_delta' => __('Use a signed decimal with at most six decimal places.')]);
        }
        $delta = BigDecimal::of((string) $value);
        if ($delta->isZero() || $delta->abs()->isGreaterThan(QuantityDecimal::MAX)) {
            throw ValidationException::withMessages(['quantity_delta' => __('The adjustment must be nonzero and within the supported range.')]);
        }
        return $delta;
    }

    private function operationKey(mixed $key): string
    {
        if (!is_string($key) || trim($key) === '' || strlen($key) > 64) {
            throw ValidationException::withMessages(['correlation_key' => __('A stock operation key of at most 64 characters is required.')]);
        }
        return trim($key);
    }

    private function assertType(string $type): void
    {
        if (!in_array($type, ['receipt', 'opening', 'opening_approved', 'sale', 'return', 'transfer_out', 'transfer_in', 'loss', 'inventory_adjustment', 'adjustment'], true)) {
            throw ValidationException::withMessages(['type' => __('This stock movement type is not supported.')]);
        }
    }

    private function lineNumber(mixed $line): int
    {
        if (!is_int($line) || $line < 1) {
            throw ValidationException::withMessages(['correlation_line' => __('The stock operation line must be a positive integer.')]);
        }
        return $line;
    }

    private function assertBucket(string $bucket, array $allowed): void
    {
        if (!in_array($bucket, $allowed, true)) {
            throw ValidationException::withMessages(['bucket' => __('This stock bucket is not supported for the operation.')]);
        }
    }

    private function add(mixed $current, mixed $delta): string
    {
        $value = BigDecimal::of((string) $current)->plus((string) $delta)->toScale(6);
        if ($value->isNegative()) {
            throw ValidationException::withMessages(['quantity' => __('Stock balance cannot become negative.')]);
        }
        return (string) $value;
    }
}
