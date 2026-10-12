<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

final class AdministrationAudit
{
    private const FIELDS = ['name','email','is_suspended','roles','permissions','password_changed','profile_image_changed','code','symbol','active','settings','changed_keys'];

    public function record(string $event, string $type, int|string|null $id, array $before = [], array $after = [], ?int $shopId = null): void
    {
        abort_unless(Schema::hasTable('audit_logs'), 503, __('administration.migration_required'));
        if (DB::transactionLevel() < 1) throw new \LogicException('Administrative audit must share the mutation transaction.');
        $request = request();
        $correlation = $request->attributes->get('administration_correlation');
        if (!$correlation) {
            $correlation = (string) Str::uuid();
            $request->attributes->set('administration_correlation', $correlation);
        }
        AuditLog::create([
            'actor_id'=>Auth::id(), 'point_of_sale_id'=>$shopId,
            'event'=>$event, 'object_type'=>$type, 'object_id'=>$id,
            'old_values'=>$this->filter($before), 'new_values'=>$this->filter($after),
            'result'=>'success', 'correlation_id'=>$correlation,
            'occurred_at'=>now('UTC'), 'recorded_at'=>now('UTC'),
        ]);
    }

    private function filter(array $values): array
    {
        $safe = array_intersect_key($values, array_flip(self::FIELDS));
        if (isset($safe['settings'])) {
            // Record changed keys and safe operational settings; never arbitrary payloads.
            $safe['settings'] = array_intersect_key((array)$safe['settings'], array_flip(SystemSettings::AUDITABLE));
        }
        return $safe;
    }
}
