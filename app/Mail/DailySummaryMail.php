<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class DailySummaryMail extends Mailable
{
    public function __construct(public object $summary, public string $kind, public string $localeCode = 'fr') {}

    public function build(): static
    {
        $data = json_decode($this->summary->payload, true, 512, JSON_THROW_ON_ERROR);
        $subject = $this->kind === 'late_activity' ? __('reporting.late_available') : ($this->summary->closure_type === 'corrected' ? __('reporting.corrected_available') : __('reporting.daily_summary'));
        $this->subject($subject.' · '.$data['shop_label'].' · '.$this->summary->business_date)
            ->view('backend.reporting.email', ['data' => $data])
            ->withSymfonyMessage(function ($message) {
                $message->getHeaders()->addIdHeader('Message-ID', 'qpos-summary-'.$this->summary->id.'-'.$this->kind.'-'.hash('sha256', json_encode($this->to)).'@qpos.local');
            });
        if ($this->kind === 'summary') {
            $pdf = app(\App\Services\DailySummaryService::class)->pdf($this->summary);
            $this->attachData($pdf, 'recap-'.$this->summary->business_date.'-v'.$this->summary->version.'.pdf', ['mime' => 'application/pdf']);
        }
        return $this;
    }
}
