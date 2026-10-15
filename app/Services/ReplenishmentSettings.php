<?php

namespace App\Services;

use App\Models\{PointOfSale, User};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ReplenishmentSettings
{
    public const DEFAULTS = [
        'schedule' => 'on_demand', 'window_days' => 30, 'review_days' => 7, 'safety_days' => 3,
        'minimum_history_days' => 14, 'daily_time' => '21:00', 'weekly_day' => 1,
        'weekly_time' => '08:00', 'critical_immediate' => true,
        'responsible_user_id' => null, 'recipient_ids' => [], 'version' => 0,
    ];

    public function get(int $shop): array
    {
        $row = DB::table('replenishment_settings')->where('point_of_sale_id', $shop)->first();
        if (!$row) return self::DEFAULTS;
        $values = array_intersect_key((array) $row, self::DEFAULTS);
        $values['recipient_ids'] = json_decode($row->recipient_ids, true, 512, JSON_THROW_ON_ERROR);
        return array_replace(self::DEFAULTS, $values);
    }

    public function actor(int $actor, int $shop): User
    {
        return app(PurchaseOrderService::class)->actor($actor, $shop, ['purchase_suggestion_manage', 'purchase_order_view', 'purchase_order_create', 'stock_view']);
    }

    public function recipient(int $actor, int $shop): User
    {
        return app(PurchaseOrderService::class)->actor($actor, $shop, ['purchase_order_view']);
    }

    public function save(int $shop, array $data, int $actor): void
    {
        $data = \Illuminate\Support\Facades\Validator::make($data, [
            'schedule'=>'required|in:on_demand,daily,weekly', 'window_days'=>'required|integer|in:30,60,90',
            'review_days'=>'required|integer|min:0|max:365', 'safety_days'=>'required|integer|min:0|max:365',
            'minimum_history_days'=>'required|integer|min:7|max:90|lte:window_days',
            'daily_time'=>'required|date_format:H:i', 'weekly_day'=>'required|integer|min:1|max:7',
            'weekly_time'=>'required|date_format:H:i', 'critical_immediate'=>'required|boolean',
            'responsible_user_id'=>'nullable|integer|exists:users,id',
            'recipient_ids'=>'nullable|array|max:50', 'recipient_ids.*'=>'integer|distinct|min:1|exists:users,id',
            'version'=>'required|integer|min:0', 'reason'=>'required|string|max:500',
        ])->validate();
        $data['responsible_user_id'] ??= null;
        $data['recipient_ids'] ??= [];
        if (trim($data['reason']) === '') throw ValidationException::withMessages(['reason'=>__('replenishment.reason_required')]);
        DB::transaction(function () use ($shop, $data, $actor) {
            \App\Support\UserAdministration::lock();
            $this->actor($actor, $shop);
            PointOfSale::whereKey($shop)->lockForUpdate()->firstOrFail();
            $before = $this->get($shop);
            if ((int) $data['version'] !== (int) $before['version'])
                throw ValidationException::withMessages(['settings' => __('replenishment.stale_draft')]);
            $values = array_intersect_key($data, self::DEFAULTS);
            if ($values['schedule'] !== 'on_demand' && empty($values['responsible_user_id']))
                throw ValidationException::withMessages(['responsible_user_id' => __('replenishment.responsible_required')]);
            if (!empty($values['responsible_user_id'])) $this->actor((int) $values['responsible_user_id'], $shop);
            foreach ($values['recipient_ids'] as $recipient) $this->recipient((int) $recipient, $shop);
            $values['recipient_ids'] = array_values(array_unique(array_map('intval', $values['recipient_ids'])));
            $values['version'] = (int) $before['version'] + 1;
            $after = $values;
            $values['recipient_ids'] = json_encode($values['recipient_ids'], JSON_THROW_ON_ERROR);
            DB::table('replenishment_settings')->updateOrInsert(['point_of_sale_id' => $shop], $values + [
                'updated_by' => $actor, 'updated_at' => now('UTC'),
                'created_at' => DB::table('replenishment_settings')->where('point_of_sale_id', $shop)->value('created_at') ?? now('UTC'),
            ]);
            app(ReplenishmentAudit::class)->record('replenishment.settings_updated', $shop, $shop, ['settings' => $before], ['settings' => $after], $data['reason'], $actor);
        }, 3);
    }
}
