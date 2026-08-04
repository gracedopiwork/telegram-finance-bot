<?php

namespace App\Mail;

use App\Models\FinancialBaseline;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CheckupResultMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $stageDisplay
     */
    public function __construct(
        public FinancialBaseline $baseline,
        public array $stageDisplay,
        public int $reviewMonths,
    ) {}

    public function envelope(): Envelope
    {
        $label = (string) ($this->stageDisplay['label'] ?? $this->baseline->stage_label ?? 'Hasil');

        return new Envelope(
            subject: 'Hasil Financial Health Check-Up — '.$label,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.checkup-result',
        );
    }
}
