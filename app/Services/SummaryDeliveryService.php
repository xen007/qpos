<?php

namespace App\Services;

use App\Mail\DailySummaryMail;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

final class SummaryDeliveryService
{
    public function run(int $limit = 20): array
    {
        $result = ['sent' => 0, 'failed' => 0, 'cancelled' => 0, 'unconfigured' => 0, 'ambiguous' => 0];
        $approval = app(SummaryMailApproval::class);
        if (! $approval->enabled()) return $result + ['disabled' => true];
        $stale = DB::table('summary_deliveries')->where('state', 'sending')->where('claimed_at', '<', now('UTC')->subMinutes(10))->pluck('id');
        foreach ($stale as $id) {
            $result['ambiguous'] += DB::table('summary_deliveries')->where('id', $id)->where('state', 'sending')->update(['state' => 'ambiguous', 'last_error' => 'delivery_confirmation_missing']);
        }
        $ids = DB::table('summary_deliveries')->whereIn('state', ['pending', 'retry'])->where('next_attempt_at', '<=', now('UTC'))->orderBy('id')->limit(min($limit, 100))->pluck('id');
        foreach ($ids as $id) {
            $claim = DB::transaction(function () use ($id, $approval) {
                $d = DB::table('summary_deliveries')->where('id', $id)->lockForUpdate()->first();
                if (! $d || ! in_array($d->state, ['pending', 'retry'], true) || $d->next_attempt_at > now('UTC')->format('Y-m-d H:i:s')) return null;
                $row = DB::table('daily_summaries')->find($d->daily_summary_id);
                $user = User::find($d->user_id);
                if (! $user || ! app(DailySummaryService::class)->eligible($user, $row->point_of_sale_id) || ($d->kind === 'late_activity' && ! $user->hasRole('Admin'))) {
                    DB::table('summary_deliveries')->where('id', $id)->update(['state' => 'cancelled', 'last_error' => 'recipient_no_longer_authorized']);
                    return ['cancelled' => true];
                }
                $destination = $approval->destination($user, $row->point_of_sale_id);
                if (! $destination) {
                    DB::table('summary_deliveries')->where('id', $id)->update(['next_attempt_at' => now('UTC')->addMinutes(15), 'last_error' => 'recipient_approval_required']);
                    return ['unconfigured' => true];
                }
                $mailer = config('reporting.mailer');
                $transport = config('mail.mailers.'.$mailer.'.transport');
                if (in_array($transport, ['log', 'array'], true) || ! $transport || ($transport === 'smtp' && ! $approval->transportReady())) {
                    DB::table('summary_deliveries')->where('id', $id)->update(['next_attempt_at' => now('UTC')->addMinutes(15), 'last_error' => 'external_mail_transport_unconfigured']);
                    return ['unconfigured' => true];
                }
                if (! filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
                    DB::table('summary_deliveries')->where('id', $id)->update(['state' => 'cancelled', 'last_error' => 'invalid_recipient_address']);
                    return ['cancelled' => true];
                }
                DB::table('summary_deliveries')->where('id', $id)->update(['state' => 'sending', 'claimed_at' => now('UTC'), 'attempts' => $d->attempts + 1, 'last_error' => null]);
                return ['delivery' => $d, 'summary' => $row, 'user' => $user, 'destination' => $destination, 'mailer' => $mailer];
            }, 3);
            if (! $claim) continue;
            if (isset($claim['cancelled'])) { $result['cancelled']++; continue; }
            if (isset($claim['unconfigured'])) { $result['unconfigured']++; continue; }
            $d = $claim['delivery'];
            $accepted = false;
            try {
                config(['mail.mailers.'.$claim['mailer'].'.timeout' => config('reporting.smtp_timeout', 20)]);
                Mail::mailer($claim['mailer'])->to($claim['destination'])->send(new DailySummaryMail($claim['summary'], $d->kind));
                $accepted = true;
                DB::transaction(function () use ($d) {
                    DB::table('summary_deliveries')->where('id', $d->id)->where('state', 'sending')->update(['state' => 'sent', 'sent_at' => now('UTC'), 'last_error' => null]);
                    $this->attempt($d, 'sent', null);
                });
                $result['sent']++;
            } catch (\Throwable $e) {
                // Never persist SMTP credentials or arbitrary exception messages.
                $error = $e instanceof \Symfony\Component\Mailer\Exception\TransportExceptionInterface ? 'mail_transport_failure' : 'delivery_generation_failure';
                $uncertain = $accepted;
                if ($e instanceof \Symfony\Component\Mailer\Exception\TransportExceptionInterface) {
                    $debug = method_exists($e, 'getDebug') ? $e->getDebug() : '';
                    $uncertain = $uncertain || str_contains($debug, 'DATA');
                }
                $state = $uncertain ? 'ambiguous' : ($d->attempts + 1 >= config('reporting.delivery_attempts', 5) ? 'failed' : 'retry');
                if ($uncertain) $error = 'delivery_confirmation_missing';
                DB::transaction(function () use ($d, $state, $error) {
                    DB::table('summary_deliveries')->where('id', $d->id)->where('state', 'sending')->update(['state' => $state, 'next_attempt_at' => now('UTC')->addMinutes(min(60, 5 * (2 ** $d->attempts))), 'last_error' => $error]);
                    $this->attempt($d, $state, $error);
                });
                $result['failed']++;
            }
        }
        return $result;
    }

    private function attempt(object $d, string $result, ?string $error): void
    {
        DB::table('summary_delivery_attempts')->insert(['summary_delivery_id' => $d->id, 'attempt' => $d->attempts + 1, 'result' => $result, 'recorded_at' => now('UTC'), 'error_code' => $error]);
    }
}
