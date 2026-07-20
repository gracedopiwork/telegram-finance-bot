<?php

namespace App\Services;

use App\Models\GoogleBusinessConnection;
use App\Models\GoogleBusinessReview;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class GoogleBusinessProfileService
{
    public const SCOPE = 'https://www.googleapis.com/auth/business.manage';

    public function isConfigured(): bool
    {
        return filled(config('services.google_business.client_id'))
            && filled(config('services.google_business.client_secret'));
    }

    public function redirectUri(): string
    {
        return (string) config('services.google_business.redirect_uri');
    }

    public function authorizationUrl(string $state): string
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('GOOGLE_BUSINESS_CLIENT_ID / CLIENT_SECRET belum di-set di .env');
        }

        return 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => config('services.google_business.client_id'),
            'redirect_uri' => $this->redirectUri(),
            'response_type' => 'code',
            'scope' => self::SCOPE,
            'access_type' => 'offline',
            'prompt' => 'consent',
            'include_granted_scopes' => 'true',
            'state' => $state,
        ]);
    }

    /**
     * @return array{access_token: string, refresh_token?: string, expires_in?: int, token_type?: string}
     */
    public function exchangeCode(string $code): array
    {
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => config('services.google_business.client_id'),
            'client_secret' => config('services.google_business.client_secret'),
            'redirect_uri' => $this->redirectUri(),
            'grant_type' => 'authorization_code',
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Gagal tukar OAuth code: '.$response->body());
        }

        return $response->json();
    }

    public function storeTokens(array $tokenPayload, ?GoogleBusinessConnection $connection = null): GoogleBusinessConnection
    {
        $connection ??= GoogleBusinessConnection::current() ?? new GoogleBusinessConnection;

        $connection->access_token = $tokenPayload['access_token'] ?? $connection->access_token;
        if (! empty($tokenPayload['refresh_token'])) {
            $connection->refresh_token = $tokenPayload['refresh_token'];
        }
        $expiresIn = (int) ($tokenPayload['expires_in'] ?? 3600);
        $connection->token_expires_at = now()->addSeconds(max(60, $expiresIn - 60));
        $connection->last_error = null;
        $connection->save();

        return $connection->fresh();
    }

    public function accessToken(GoogleBusinessConnection $connection): string
    {
        if (! $connection->isConnected()) {
            throw new RuntimeException('Google Business Profile belum terhubung.');
        }

        if ($connection->token_expires_at && $connection->token_expires_at->isFuture() && filled($connection->access_token)) {
            return $connection->access_token;
        }

        if (! filled($connection->refresh_token)) {
            throw new RuntimeException('Refresh token kosong — hubungkan ulang Google Business Profile.');
        }

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => config('services.google_business.client_id'),
            'client_secret' => config('services.google_business.client_secret'),
            'refresh_token' => $connection->refresh_token,
            'grant_type' => 'refresh_token',
        ]);

        if (! $response->successful()) {
            $connection->update(['last_error' => 'Refresh token gagal: '.$response->body()]);
            throw new RuntimeException('Gagal refresh token Google: '.$response->body());
        }

        $payload = $response->json();
        $this->storeTokens($payload, $connection);

        return (string) $payload['access_token'];
    }

    /**
     * @return list<array{name: string, accountName: string, type?: string}>
     */
    public function listAccounts(GoogleBusinessConnection $connection): array
    {
        $token = $this->accessToken($connection);
        $response = Http::withToken($token)
            ->acceptJson()
            ->get('https://mybusinessaccountmanagement.googleapis.com/v1/accounts');

        if (! $response->successful()) {
            throw new RuntimeException('Gagal ambil accounts: '.$this->formatError($response->json(), $response->body()));
        }

        return $response->json('accounts') ?? [];
    }

    /**
     * @return list<array{name: string, title?: string, storefrontAddress?: array}>
     */
    public function listLocations(GoogleBusinessConnection $connection, string $accountName): array
    {
        $token = $this->accessToken($connection);
        $accountId = $this->resourceId($accountName, 'accounts');

        $response = Http::withToken($token)
            ->acceptJson()
            ->get("https://mybusinessbusinessinformation.googleapis.com/v1/accounts/{$accountId}/locations", [
                'readMask' => 'name,title,storefrontAddress,metadata',
                'pageSize' => 100,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Gagal ambil locations: '.$this->formatError($response->json(), $response->body()));
        }

        return $response->json('locations') ?? [];
    }

    /**
     * Sync semua halaman reviews ke DB + update rating summary di settings.
     *
     * @return array{synced: int, average_rating: ?float, total_review_count: ?int}
     */
    public function syncReviews(?GoogleBusinessConnection $connection = null): array
    {
        $connection ??= GoogleBusinessConnection::current();
        if (! $connection || ! $connection->hasLocation()) {
            throw new RuntimeException('Pilih location Google Business dulu sebelum sync.');
        }

        $token = $this->accessToken($connection);
        $accountId = $this->resourceId($connection->account_name, 'accounts');
        $locationId = $this->resourceId($connection->location_name, 'locations');

        $pageToken = null;
        $synced = 0;
        $averageRating = null;
        $totalReviewCount = null;
        $sort = 0;

        do {
            $query = ['pageSize' => 50];
            if ($pageToken) {
                $query['pageToken'] = $pageToken;
            }

            $response = Http::withToken($token)
                ->acceptJson()
                ->get("https://mybusiness.googleapis.com/v4/accounts/{$accountId}/locations/{$locationId}/reviews", $query);

            if (! $response->successful()) {
                $message = $this->formatError($response->json(), $response->body());
                $connection->update(['last_error' => $message]);
                throw new RuntimeException('Gagal sync reviews: '.$message);
            }

            $data = $response->json();
            $averageRating = $data['averageRating'] ?? $averageRating;
            $totalReviewCount = $data['totalReviewCount'] ?? $totalReviewCount;

            foreach ($data['reviews'] ?? [] as $review) {
                $comment = trim(strip_tags((string) ($review['comment'] ?? '')));
                $name = trim((string) ($review['reviewer']['displayName'] ?? 'Google user'));
                $reviewId = (string) ($review['reviewId'] ?? $review['name'] ?? Str::uuid());

                GoogleBusinessReview::updateOrCreate(
                    ['google_review_id' => $reviewId],
                    [
                        'reviewer_name' => $name !== '' ? $name : 'Google user',
                        'reviewer_photo_url' => $review['reviewer']['profilePhotoUrl'] ?? null,
                        'rating' => $this->mapStarRating($review['starRating'] ?? null),
                        'comment' => $comment !== '' ? $comment : null,
                        'reviewed_at' => isset($review['createTime']) ? Carbon::parse($review['createTime']) : null,
                        'reply_comment' => isset($review['reviewReply']['comment'])
                            ? trim(strip_tags((string) $review['reviewReply']['comment']))
                            : null,
                        'reply_updated_at' => isset($review['reviewReply']['updateTime'])
                            ? Carbon::parse($review['reviewReply']['updateTime'])
                            : null,
                        'sort' => $sort++,
                    ]
                );
                $synced++;
            }

            $pageToken = $data['nextPageToken'] ?? null;
        } while ($pageToken);

        $connection->update([
            'average_rating' => $averageRating,
            'total_review_count' => $totalReviewCount,
            'last_synced_at' => now(),
            'last_error' => null,
        ]);

        if ($averageRating !== null) {
            Setting::put('reviews.google_rating', (string) $averageRating, 'text', 'reviews', 'Rating Google (contoh: 5.0)');
        }
        if ($totalReviewCount !== null) {
            Setting::put('reviews.google_count', (string) $totalReviewCount, 'text', 'reviews', 'Jumlah ulasan Google');
        }
        Setting::bust();

        return [
            'synced' => $synced,
            'average_rating' => $averageRating !== null ? (float) $averageRating : null,
            'total_review_count' => $totalReviewCount !== null ? (int) $totalReviewCount : null,
        ];
    }

    public function disconnect(?GoogleBusinessConnection $connection = null): void
    {
        $connection ??= GoogleBusinessConnection::current();
        if ($connection) {
            $connection->delete();
        }
    }

    private function resourceId(?string $name, string $prefix): string
    {
        $name = trim((string) $name);
        if ($name === '') {
            throw new RuntimeException("Resource {$prefix} kosong.");
        }
        if (str_starts_with($name, $prefix.'/')) {
            return substr($name, strlen($prefix) + 1);
        }

        return $name;
    }

    private function mapStarRating(mixed $starRating): int
    {
        return match ((string) $starRating) {
            'ONE', '1' => 1,
            'TWO', '2' => 2,
            'THREE', '3' => 3,
            'FOUR', '4' => 4,
            'FIVE', '5' => 5,
            default => 5,
        };
    }

    private function formatError(mixed $json, string $fallback): string
    {
        if (is_array($json)) {
            $msg = $json['error']['message'] ?? $json['error_description'] ?? null;
            if (is_string($msg) && $msg !== '') {
                return $msg;
            }
        }

        return Str::limit($fallback, 500);
    }
}
