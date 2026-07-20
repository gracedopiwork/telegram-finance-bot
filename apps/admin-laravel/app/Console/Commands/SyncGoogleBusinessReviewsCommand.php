<?php

namespace App\Console\Commands;

use App\Services\GoogleBusinessProfileService;
use Illuminate\Console\Command;
use Throwable;

class SyncGoogleBusinessReviewsCommand extends Command
{
    protected $signature = 'google-reviews:sync';

    protected $description = 'Sync semua ulasan dari Google Business Profile API ke database';

    public function handle(GoogleBusinessProfileService $gbp): int
    {
        if (! $gbp->isConfigured()) {
            $this->warn('GOOGLE_BUSINESS_CLIENT_ID/SECRET belum di-set — skip.');

            return self::SUCCESS;
        }

        try {
            $result = $gbp->syncReviews();
            $this->info("Synced {$result['synced']} reviews (rating {$result['average_rating']}, total {$result['total_review_count']}).");

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
