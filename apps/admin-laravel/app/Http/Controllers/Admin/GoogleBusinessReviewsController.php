<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GoogleBusinessConnection;
use App\Models\GoogleBusinessReview;
use App\Services\GoogleBusinessProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class GoogleBusinessReviewsController extends Controller
{
    public function __construct(private GoogleBusinessProfileService $gbp)
    {
    }

    public function connect(Request $request): RedirectResponse
    {
        if (! $this->gbp->isConfigured()) {
            return redirect()
                ->route('admin.settings.index', ['group' => 'reviews'])
                ->with('error', 'Set GOOGLE_BUSINESS_CLIENT_ID dan GOOGLE_BUSINESS_CLIENT_SECRET di .env dulu. Pastikan project sudah disetujui akses Business Profile API.');
        }

        $state = bin2hex(random_bytes(16));
        $request->session()->put('gbp_oauth_state', $state);

        return redirect()->away($this->gbp->authorizationUrl($state));
    }

    public function callback(Request $request): RedirectResponse
    {
        $expected = $request->session()->pull('gbp_oauth_state');
        if (! $expected || ! hash_equals((string) $expected, (string) $request->query('state'))) {
            return redirect()
                ->route('admin.settings.index', ['group' => 'reviews'])
                ->with('error', 'State OAuth tidak valid. Coba hubungkan ulang.');
        }

        if ($request->filled('error')) {
            return redirect()
                ->route('admin.settings.index', ['group' => 'reviews'])
                ->with('error', 'OAuth dibatalkan: '.$request->query('error'));
        }

        $code = (string) $request->query('code', '');
        if ($code === '') {
            return redirect()
                ->route('admin.settings.index', ['group' => 'reviews'])
                ->with('error', 'Kode OAuth kosong.');
        }

        try {
            $tokens = $this->gbp->exchangeCode($code);
            $connection = $this->gbp->storeTokens($tokens);

            $accounts = $this->gbp->listAccounts($connection);
            if ($accounts === []) {
                return redirect()
                    ->route('admin.settings.index', ['group' => 'reviews'])
                    ->with('error', 'Tidak ada Google Business account. Pastikan login dengan akun pemilik / manager listing YFD.');
            }

            $locationChoices = [];
            $primaryAccountName = null;
            $primaryAccountLabel = null;

            foreach ($accounts as $account) {
                $accountName = (string) ($account['name'] ?? '');
                if ($accountName === '') {
                    continue;
                }
                $accountLabel = (string) ($account['accountName'] ?? $accountName);
                $primaryAccountName ??= $accountName;
                $primaryAccountLabel ??= $accountLabel;

                try {
                    foreach ($this->gbp->listLocations($connection, $accountName) as $loc) {
                        $locationChoices[] = [
                            'account_name' => $accountName,
                            'account_label' => $accountLabel,
                            'name' => $loc['name'] ?? '',
                            'title' => $loc['title'] ?? ($loc['name'] ?? 'Location'),
                        ];
                    }
                } catch (Throwable $e) {
                    Log::info('GBP listLocations skipped for account', [
                        'account' => $accountName,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $connection->update([
                'account_name' => $primaryAccountName,
                'account_label' => $primaryAccountLabel,
            ]);

            $locationChoices = array_values(array_filter($locationChoices, fn ($l) => filled($l['name'])));

            if (count($locationChoices) === 1) {
                $loc = $locationChoices[0];
                $connection->update([
                    'account_name' => $loc['account_name'],
                    'account_label' => $loc['account_label'],
                    'location_name' => $loc['name'],
                    'location_title' => $loc['title'],
                ]);
                $result = $this->gbp->syncReviews($connection->fresh());

                return redirect()
                    ->route('admin.settings.index', ['group' => 'reviews'])
                    ->with('success', "Terhubung & sync {$result['synced']} ulasan Google.");
            }

            if ($locationChoices === []) {
                return redirect()
                    ->route('admin.settings.index', ['group' => 'reviews'])
                    ->with('error', 'Login berhasil, tapi tidak ada lokasi terverifikasi. Pastikan listing YFD sudah verified di Google Business Profile.');
            }

            $request->session()->put('gbp_pending_locations', [
                'account_name' => $primaryAccountName,
                'account_label' => $primaryAccountLabel,
                'locations' => $locationChoices,
            ]);

            return redirect()
                ->route('admin.google-reviews.pick-location')
                ->with('success', 'Login berhasil. Pilih lokasi bisnis YFD.');
        } catch (Throwable $e) {
            Log::warning('GBP OAuth callback failed', ['error' => $e->getMessage()]);

            return redirect()
                ->route('admin.settings.index', ['group' => 'reviews'])
                ->with('error', 'Gagal hubungkan Google: '.$e->getMessage());
        }
    }

    public function pickLocationForm(Request $request): View|RedirectResponse
    {
        $pending = $request->session()->get('gbp_pending_locations');
        if (! is_array($pending) || empty($pending['locations'])) {
            return redirect()->route('admin.settings.index', ['group' => 'reviews']);
        }

        return view('admin.google_reviews.pick_location', [
            'pending' => $pending,
        ]);
    }

    public function pickLocation(Request $request): RedirectResponse
    {
        $pending = $request->session()->get('gbp_pending_locations');
        if (! is_array($pending)) {
            return redirect()->route('admin.settings.index', ['group' => 'reviews'])
                ->with('error', 'Sesi pilih lokasi habis. Hubungkan ulang.');
        }

        $validated = $request->validate([
            'location_name' => ['required', 'string'],
        ]);

        $match = collect($pending['locations'] ?? [])->firstWhere('name', $validated['location_name']);
        if (! $match) {
            return back()->with('error', 'Lokasi tidak valid.');
        }

        $connection = GoogleBusinessConnection::current();
        if (! $connection) {
            return redirect()->route('admin.settings.index', ['group' => 'reviews'])
                ->with('error', 'Koneksi tidak ditemukan. Hubungkan ulang.');
        }

        $connection->update([
            'account_name' => $match['account_name'] ?? ($pending['account_name'] ?? $connection->account_name),
            'account_label' => $match['account_label'] ?? ($pending['account_label'] ?? $connection->account_label),
            'location_name' => $match['name'],
            'location_title' => $match['title'] ?? null,
        ]);

        $request->session()->forget('gbp_pending_locations');

        try {
            $result = $this->gbp->syncReviews($connection->fresh());

            return redirect()
                ->route('admin.settings.index', ['group' => 'reviews'])
                ->with('success', "Lokasi disimpan & sync {$result['synced']} ulasan Google.");
        } catch (Throwable $e) {
            return redirect()
                ->route('admin.settings.index', ['group' => 'reviews'])
                ->with('error', 'Lokasi disimpan, tapi sync gagal: '.$e->getMessage());
        }
    }

    public function sync(): RedirectResponse
    {
        try {
            $result = $this->gbp->syncReviews();

            return redirect()
                ->route('admin.settings.index', ['group' => 'reviews'])
                ->with('success', "Sync selesai: {$result['synced']} ulasan (rating {$result['average_rating']}, total {$result['total_review_count']}).");
        } catch (Throwable $e) {
            return redirect()
                ->route('admin.settings.index', ['group' => 'reviews'])
                ->with('error', 'Sync gagal: '.$e->getMessage());
        }
    }

    public function disconnect(): RedirectResponse
    {
        $this->gbp->disconnect();

        return redirect()
            ->route('admin.settings.index', ['group' => 'reviews'])
            ->with('success', 'Koneksi Google Business Profile diputus. Ulasan yang sudah tersimpan tetap ada.');
    }

    public function toggle(GoogleBusinessReview $review): RedirectResponse
    {
        $review->update(['is_published' => ! $review->is_published]);

        return redirect()
            ->route('admin.settings.index', ['group' => 'reviews'])
            ->with('success', 'Status tampil ulasan diperbarui.');
    }
}
