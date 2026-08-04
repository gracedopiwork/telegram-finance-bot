<?php

namespace App\Services;

use App\Mail\CheckupResultMail;
use App\Models\FinancialBaseline;
use Illuminate\Support\Facades\Mail;

class CheckupResultMailer
{
    /**
     * Kirim hasil check-up hanya ke email pada baseline tersebut (bukan user lain).
     */
    public function send(FinancialBaseline $baseline): void
    {
        $email = strtolower(trim((string) $baseline->email));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $stageDisplay = app(DiagnosticConfigService::class)->stageDisplay(
            (string) $baseline->financial_stage,
            (int) $baseline->financial_stage_score,
        );
        $reviewMonths = max(1, (int) config('baseline_assessment.review_months', 3));

        Mail::to($email)->send(new CheckupResultMail($baseline, $stageDisplay, $reviewMonths));
    }
}
