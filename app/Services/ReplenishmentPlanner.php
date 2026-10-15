<?php

namespace App\Services;

use App\Models\{PointOfSale, PurchaseOrder, User};
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\{DB, Schema, Validator};
use Illuminate\Validation\ValidationException;

final class ReplenishmentPlanner
{
    public function ready(): bool
    {
        return Schema::hasTable('replenishment_runs') && Schema::hasTable('replenishment_run_items') && Schema::hasTable('replenishment_settings');
    }

    public function manual(int $shop, int $actor, array $input): object
    {
        $data = Validator::make($input, [
            'operation_key'=>'required|string|min:8|max:96', 'product_ids'=>'required|array|min:1|max:200',
            'product_ids.*'=>'required|integer|distinct|exists:products,id',
            'supplier_id'=>'nullable|integer|exists:suppliers,id', 'window_days'=>'required|integer|in:30,60,90',
        ])->validate();
        if(\App\Models\Product::whereIn('id',$data['product_ids'])->where('status',false)->exists())
            throw ValidationException::withMessages(['product_ids'=>__('replenishment.product_unavailable')]);
        $settings = app(ReplenishmentSettings::class)->get($shop);
        $settings['window_days'] = (int) $data['window_days'];
        $settings['selection'] = array_map('intval', $data['product_ids']); sort($settings['selection']);
        $settings['supplier_id'] = isset($data['supplier_id']) ? (int) $data['supplier_id'] : null;
        $settings['order_mode'] = 'hybrid';
        if (!$settings['recipient_ids']) $settings['recipient_ids'] = [$actor];
        $slot = 'manual:'.hash('sha256', $data['operation_key']);
        $hash = hash('sha256', json_encode([$actor, $shop, $data], JSON_THROW_ON_ERROR));
        $run = DB::transaction(function () use ($shop, $actor, $settings, $slot, $hash) {
            User::whereKey($actor)->lockForUpdate()->firstOrFail();
            app(ReplenishmentSettings::class)->actor($actor, $shop);
            PointOfSale::whereKey($shop)->lockForUpdate()->firstOrFail();
            return $this->enqueue($shop, $actor, $slot, 'manual', $hash, $settings);
        }, 3);
        $this->process((int) $run->id);
        return DB::table('replenishment_runs')->find($run->id);
    }

    public function schedule(string $mode, int $limit = 5): array
    {
        if (!$this->ready()) return ['unavailable'=>true];
        if (!in_array($mode, ['daily','weekly'], true)) throw new \InvalidArgumentException('Unknown schedule');
        $now = CarbonImmutable::now('Africa/Douala');
        $queued = 0;
        DB::table('replenishment_settings')->where('schedule', $mode)->orderBy('id')->chunkById(100, function ($rows) use ($mode, $now, &$queued) {
            foreach ($rows as $row) {
                if (!PointOfSale::whereKey($row->point_of_sale_id)->where('is_active', true)->exists() || !$row->responsible_user_id) continue;
                $settings = app(ReplenishmentSettings::class)->get((int) $row->point_of_sale_id);
                $due = $mode === 'daily' ? $now->startOfDay()->setTimeFromTimeString($settings['daily_time'])
                    : $now->startOfWeek()->addDays((int) $settings['weekly_day'] - 1)->setTimeFromTimeString($settings['weekly_time']);
                if ($now->lessThan($due)) continue;
                $slot = $mode.':'.$due->toDateString();
                $settings['selection'] = null; $settings['supplier_id'] = null; $settings['order_mode'] = 'auto';
                DB::transaction(function () use ($row, $slot, $mode, $settings, &$queued) {
                    PointOfSale::whereKey($row->point_of_sale_id)->lockForUpdate()->firstOrFail();
                    $existed = DB::table('replenishment_runs')->where('point_of_sale_id',$row->point_of_sale_id)->where('slot',$slot)->exists();
                    $this->enqueue((int) $row->point_of_sale_id, (int) $row->responsible_user_id, $slot, $mode,
                        hash('sha256', json_encode($settings, JSON_THROW_ON_ERROR)), $settings);
                    if (!$existed) $queued++;
                }, 3);
            }
        });
        return ['queued'=>$queued, 'processed'=>$this->drain($limit)];
    }

    private function enqueue(int $shop, int $actor, string $slot, string $trigger, string $hash, array $settings): object
    {
        DB::table('replenishment_runs')->insertOrIgnore([
            'point_of_sale_id'=>$shop, 'user_id'=>$actor, 'slot'=>$slot, 'trigger'=>$trigger, 'request_hash'=>$hash,
            'settings_snapshot'=>json_encode($settings, JSON_THROW_ON_ERROR), 'available_at'=>now('UTC'),
            'created_at'=>now('UTC'), 'updated_at'=>now('UTC'),
        ]);
        $run = DB::table('replenishment_runs')->where('point_of_sale_id',$shop)->where('slot',$slot)->lockForUpdate()->first();
        if ($trigger === 'manual' && (!hash_equals($run->request_hash,$hash) || (int)$run->user_id !== $actor))
            throw ValidationException::withMessages(['operation_key'=>__('replenishment.key_conflict')]);
        return $run;
    }

    public function drain(int $limit = 5): int
    {
        if (!$this->ready()) return 0;
        $ids = DB::table('replenishment_runs')->whereIn('state',['queued','retry'])->where('available_at','<=',now('UTC'))
            ->orderBy('id')->limit(max(1,min(20,$limit)))->pluck('id');
        foreach ($ids as $id) $this->process((int)$id);
        return $ids->count();
    }

    public function process(int $id): void
    {
        $header = DB::table('replenishment_runs')->find($id);
        if (!$header) return;
        try {
            DB::transaction(function () use ($id, $header) {
                User::whereKey($header->user_id)->lockForUpdate()->firstOrFail();
                PointOfSale::whereKey($header->point_of_sale_id)->lockForUpdate()->firstOrFail();
                $run = DB::table('replenishment_runs')->where('id',$id)->lockForUpdate()->first();
                if (!in_array($run->state,['queued','retry'],true) || $run->available_at > now('UTC')->format('Y-m-d H:i:s')) return;
                if($run->trigger !== 'manual'){
                    $current=app(ReplenishmentSettings::class)->get((int)$run->point_of_sale_id);
                    if($current['schedule']!==$run->trigger || (int)$current['responsible_user_id']!==(int)$run->user_id){
                        DB::table('replenishment_runs')->where('id',$id)->update(['state'=>'cancelled','last_error'=>'schedule_disabled','completed_at'=>now('UTC'),'updated_at'=>now('UTC')]);
                        return;
                    }
                }
                app(ReplenishmentSettings::class)->actor((int)$run->user_id,(int)$run->point_of_sale_id);
                $settings = json_decode($run->settings_snapshot,true,512,JSON_THROW_ON_ERROR);
                $query = app(PurchaseOrderSuggestionService::class)->products();
                if (is_array($settings['selection'] ?? null)) $query->whereIn('products.id',$settings['selection']);
                $ids = (clone $query)->where('products.id','>',$run->cursor)->orderBy('products.id')->limit(200)->pluck('products.id')->all();
                $results = app(PurchaseOrderSuggestionService::class)->getSuggestions((int)$run->user_id,(int)$run->point_of_sale_id,$ids,$settings);
                $groups = []; $records = [];
                foreach ($results as $result) {
                    $existing = DB::table('purchase_order_items as i')->join('purchase_orders as o','o.id','=','i.purchase_order_id')
                        ->where('o.point_of_sale_id',$run->point_of_sale_id)->where('o.status','draft')
                        ->where('i.product_id',$result['product_id'])->orderBy('o.id')->value('o.id');
                    $state = $existing ? 'existing_draft' : ($result['can_draft'] ? 'pending' : (\Brick\Math\BigDecimal::of($result['need'])->isZero() ? 'not_needed' : 'incomplete'));
                    if (!$existing && $result['can_draft']) $groups[$result['supplier_id']][] = $result;
                    $records[$result['product_id']] = [
                        'replenishment_run_id'=>$run->id,'product_id'=>$result['product_id'],'purchase_order_id'=>$existing,
                        'state'=>$state,'snapshot'=>json_encode($result,JSON_THROW_ON_ERROR),'created_at'=>now('UTC'),'updated_at'=>now('UTC'),
                    ];
                }
                foreach ($groups as $supplier => $lines) {
                    $key = 'suggestion:'.hash('sha256',$run->id.'|'.$supplier.'|'.($ids[0] ?? 0));
                    $dates = array_filter(array_column($lines,'expected_date')); sort($dates);
                    $items = array_map(fn($line)=>[
                        'product_id'=>$line['product_id'],'product_unit_id'=>$line['product_unit_id'],
                        'quantity_ordered'=>$line['quantity_packs'],'unit_price'=>$line['unit_price'],'discount_amount'=>'0','suggestion'=>$line,
                    ],$lines);
                    $po = app(PurchaseOrderService::class)->create([
                        'operation_key'=>$key,'supplier_id'=>(int)$supplier,'mode'=>$settings['order_mode'],
                        'expected_date'=>$dates ? end($dates) : null,'notes'=>__('replenishment.generated_note',['run'=>$run->id]),'items'=>$items,
                    ],(int)$run->user_id,(int)$run->point_of_sale_id,$key);
                    foreach ($lines as $line) {
                        $records[$line['product_id']]['purchase_order_id']=$po->id;
                        $records[$line['product_id']]['state']='drafted';
                    }
                    $this->notifyDraft($po,$settings['recipient_ids'],(int)$run->id);
                }
                foreach ($records as $record) DB::table('replenishment_run_items')->insert($record);
                $cursor = $ids ? max($ids) : $run->cursor;
                $more = (clone $query)->where('products.id','>',$cursor)->exists();
                DB::table('replenishment_runs')->where('id',$id)->update([
                    'cursor'=>$cursor,'state'=>$more?'queued':'complete','attempts'=>0,'last_error'=>null,
                    'completed_at'=>$more?null:now('UTC'),'available_at'=>now('UTC'),'updated_at'=>now('UTC'),
                ]);
            },3);
        } catch (\Throwable $error) {
            DB::transaction(function () use ($id,$error) {
                $run=DB::table('replenishment_runs')->where('id',$id)->lockForUpdate()->first();
                if (!$run || $run->state==='complete') return;
                $attempts=(int)$run->attempts+1;
                $code=$error instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface || $error instanceof \Illuminate\Database\Eloquent\ModelNotFoundException
                    ?'authorization_unavailable':($error instanceof ValidationException?'invalid_source_data':'generation_failed');
                DB::table('replenishment_runs')->where('id',$id)->update(['state'=>$attempts>=5?'failed':'retry','attempts'=>$attempts,
                    'last_error'=>$code,'available_at'=>now('UTC')->addMinutes(min(60,2**$attempts)),'updated_at'=>now('UTC')]);
            },3);
        }
    }

    private function notifyDraft(PurchaseOrder $order, array $recipients, int $run): void
    {
        foreach (array_unique($recipients) as $id) {
            $user=User::find($id);
            $event=(object)['kind'=>'purchase_order_created','point_of_sale_id'=>$order->point_of_sale_id,'source_id'=>$order->id,'target_user_id'=>$id];
            if (!$user || !\App\Support\NotificationCatalog::allowed($user,$event)) continue;
            app(OperationalNotifications::class)->record('purchase_order_created','po-created:'.$order->id.':'.$id,
                (int)$order->point_of_sale_id,(int)$order->id,['reference'=>$order->reference,'run_id'=>$run],(int)$id);
        }
    }
}
