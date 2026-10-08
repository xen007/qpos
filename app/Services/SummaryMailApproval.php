<?php

namespace App\Services;

use App\Models\PointOfSale;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class SummaryMailApproval
{
    public const FIELDS = ['business_date', 'shop', 'version', 'cutoff', 'net_sales', 'cogs', 'gross_margin', 'expenses', 'management_result', 'treasury', 'cash_difference', 'manual_cash', 'native_debt', 'peak_hours', 'open_sessions_and_cashier_names', 'pdf_attachment', 'authenticated_link', 'late_activity_notice'];

    public function enabled(): bool
    {
        $value = DB::table('reporting_runtime')->where('key', 'mail_enabled')->value('value');
        return $value === null ? (bool) config('system.daily_summary_email_enabled', false) : $value === '1';
    }

    public function recipients(): array
    {
        $value = DB::table('reporting_runtime')->where('key', 'mail_recipients')->value('value');
        return $value === null ? (array) config('system.daily_summary_email_recipients', []) : json_decode($value, true, 512, JSON_THROW_ON_ERROR);
    }

    public function transportReady(): bool
    {
        $mailer = config('reporting.mailer');
        $transport = config('mail.mailers.'.$mailer.'.transport');
        if ($transport !== 'smtp') return false;
        if (config('mail.mailers.'.$mailer.'.url')) return true;
        $host = config('mail.mailers.'.$mailer.'.host');
        if (! $host || in_array($host, ['mailpit', 'mailhog', 'localhost', '127.0.0.1'], true)) return false;
        return (bool) config('mail.mailers.'.$mailer.'.username') && (bool) config('mail.mailers.'.$mailer.'.password') && (bool) config('mail.mailers.'.$mailer.'.port');
    }

    public function preview(User $actor): array
    {
        $pairs = [];
        foreach (PointOfSale::accessibleBy($actor)->orderBy('points_of_sale.id')->get() as $shop) {
            foreach (User::where('is_suspended', false)->orderBy('id')->get() as $user) {
                if (app(DailySummaryService::class)->eligible($user, $shop->id) && filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
                    $pairs[] = ['user_id' => $user->id, 'name' => $user->name, 'account_email' => $user->email, 'email' => $user->email, 'shop_id' => $shop->id, 'shop' => $shop->name];
                }
            }
        }
        $manifest = ['template' => 'phase5-daily-v1', 'fields' => self::FIELDS, 'pairs' => $pairs];
        $manifest['hash'] = hash('sha256', json_encode($manifest, JSON_THROW_ON_ERROR));
        return $manifest;
    }

    public function approved(User $user, int $shop): bool
    {
        return $this->destination($user, $shop) !== null;
    }

    public function destination(User $user, int $shop): string|array|null
    {
        $value = DB::table('reporting_runtime')->where('key', 'mail_approval')->value('value');
        if (! $value) return null;
        $manifest = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        if (($manifest['template'] ?? '') !== 'phase5-daily-v1' || ($manifest['fields'] ?? []) !== self::FIELDS) return null;
        foreach ($manifest['pairs'] ?? [] as $pair) {
            if ((int) $pair['user_id'] !== $user->id || (int) $pair['shop_id'] !== $shop || ! hash_equals($pair['account_email'] ?? $pair['email'] ?? '', $user->email)) continue;
            if (isset($pair['emails'])) {
                $emails = $pair['emails'];
                if (! is_array($emails) || ! $emails || count($emails) > 20) return null;
                foreach ($emails as $email) if (! is_string($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) return null;
                return array_values(array_unique($emails));
            }
            if (filter_var($pair['email'] ?? '', FILTER_VALIDATE_EMAIL)) return $pair['email'];
        }
        return null;
    }
}
