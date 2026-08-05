<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\BotTransaction;
use App\Services\TransactionImportService;
use App\Support\PortalSession;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionsController extends Controller
{
    private function activeTelegramUserId(Request $request): int
    {
        return (int) PortalSession::telegramUserId($request);
    }

    public function importTemplate(): StreamedResponse
    {
        $csv = app(TransactionImportService::class)->templateCsv();

        return response()->streamDownload(function () use ($csv): void {
            echo $csv;
        }, 'yfd-template-transaksi.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function export(Request $request): StreamedResponse|RedirectResponse
    {
        $telegramUserId = $this->activeTelegramUserId($request);
        if ($telegramUserId <= 0) {
            return redirect()
                ->route('portal.login')
                ->with('warning', 'Sesi portal habis. Silakan login ulang sebelum export.');
        }

        $xlsx = app(TransactionImportService::class)->exportXlsxForUser($telegramUserId);
        $filename = 'yfd-transaksi-semua-'.now()->format('Ymd-His').'.xlsx';

        return response()->streamDownload(function () use ($xlsx): void {
            echo $xlsx;
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            // Excel/Google Sheets CSV often reports as application/vnd.ms-excel or text/plain, not text/csv.
            'file' => [
                'required',
                'file',
                'extensions:csv,txt,xls,xlsx',
                'mimetypes:text/csv,text/plain,application/csv,application/vnd.ms-excel,text/x-csv,application/octet-stream,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'max:5120',
            ],
        ]);

        $telegramUserId = (int) PortalSession::telegramUserId($request);
        if ($telegramUserId <= 0) {
            return redirect()
                ->route('portal.login')
                ->with('warning', 'Sesi portal habis. Silakan login ulang sebelum import.');
        }

        $result = app(TransactionImportService::class)->importFromFile(
            $telegramUserId,
            $request->file('file'),
        );

        if ($result['imported'] === 0 && $result['failed'] === 0 && ! empty($result['errors'])) {
            return redirect()
                ->route('portal.transactions', $request->only(['month', 'period']))
                ->with('warning', implode(' ', $result['errors']));
        }

        $message = "{$result['imported']} transaksi berhasil diimpor.";
        if ($result['failed'] > 0) {
            $message .= " {$result['failed']} baris gagal.";
        }
        if (! empty($result['focus_month'])) {
            $monthLabel = \Carbon\Carbon::createFromFormat('Y-m', $result['focus_month'])->translatedFormat('F Y');
            $message .= " Buka periode {$monthLabel} di filter bulan untuk melihat semua data.";
        }

        $month = $result['focus_month'] ?? null;
        $redirect = redirect()
            ->route('portal.transactions', [
                'month' => is_string($month) && $month !== '' ? $month : $request->query('month'),
                'period' => $request->query('period', 1),
            ])
            ->with('success', $message);

        if (! empty($result['errors'])) {
            $redirect->with('import_errors', array_slice($result['errors'], 0, 20));
        }

        return $redirect;
    }

    public function destroy(Request $request, BotTransaction $transaction): RedirectResponse|JsonResponse
    {
        $telegramUserId = $this->activeTelegramUserId($request);
        if ($telegramUserId <= 0) {
            if ($request->wantsJson()) {
                return response()->json(['ok' => false, 'message' => 'Sesi portal habis. Silakan login ulang.'], 401);
            }

            return redirect()
                ->route('portal.login')
                ->with('warning', 'Sesi portal habis. Silakan login ulang.');
        }

        if ((int) $transaction->telegram_user_id !== $telegramUserId) {
            if ($request->wantsJson()) {
                return response()->json(['ok' => false, 'message' => 'Transaksi tidak ditemukan atau bukan milik akun Anda.'], 403);
            }

            return redirect()
                ->route('portal.transactions', $request->only(['month', 'period']))
                ->with('warning', 'Transaksi tidak ditemukan atau bukan milik akun Anda.');
        }

        $transactionId = $transaction->id;
        $transaction->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Transaksi berhasil dihapus.',
                'id' => $transactionId,
            ]);
        }

        return redirect()
            ->route('portal.transactions', $request->only(['month', 'period']))
            ->with('success', 'Transaksi berhasil dihapus.');
    }

    public function destroySelected(Request $request): RedirectResponse|JsonResponse
    {
        $telegramUserId = $this->activeTelegramUserId($request);
        if ($telegramUserId <= 0) {
            if ($request->wantsJson()) {
                return response()->json(['ok' => false, 'message' => 'Sesi portal habis. Silakan login ulang.'], 401);
            }

            return redirect()
                ->route('portal.login')
                ->with('warning', 'Sesi portal habis. Silakan login ulang.');
        }

        $validated = $request->validate([
            'transaction_ids' => ['required', 'array', 'min:1'],
            'transaction_ids.*' => ['integer', 'min:1'],
        ]);

        $ids = collect($validated['transaction_ids'])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            $message = 'Pilih minimal satu transaksi untuk dihapus.';
            if ($request->wantsJson()) {
                return response()->json(['ok' => false, 'message' => $message], 422);
            }

            return redirect()
                ->route('portal.transactions', $request->only(['month', 'period']))
                ->with('warning', $message);
        }

        $deleted = BotTransaction::query()
            ->where('telegram_user_id', $telegramUserId)
            ->whereIn('id', $ids->all())
            ->delete();

        $message = $deleted > 0
            ? "{$deleted} transaksi berhasil dihapus."
            : 'Tidak ada transaksi yang bisa dihapus.';

        if ($request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'message' => $message,
                'deleted' => $deleted,
            ]);
        }

        return redirect()
            ->route('portal.transactions', $request->only(['month', 'period']))
            ->with('success', $message);
    }

    public function destroyMonth(Request $request): RedirectResponse|JsonResponse
    {
        $telegramUserId = $this->activeTelegramUserId($request);
        if ($telegramUserId <= 0) {
            if ($request->wantsJson()) {
                return response()->json(['ok' => false, 'message' => 'Sesi portal habis. Silakan login ulang.'], 401);
            }

            return redirect()
                ->route('portal.login')
                ->with('warning', 'Sesi portal habis. Silakan login ulang.');
        }

        $validated = $request->validate([
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
        ]);

        $month = (string) $validated['month'];
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $deleted = BotTransaction::query()
            ->where('telegram_user_id', $telegramUserId)
            ->whereBetween('recorded_at', [$start, $end])
            ->delete();

        $monthLabel = $start->translatedFormat('F Y');
        $message = $deleted > 0
            ? "{$deleted} transaksi bulan {$monthLabel} berhasil dihapus."
            : "Tidak ada transaksi bulan {$monthLabel} yang bisa dihapus.";

        if ($request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'message' => $message,
                'deleted' => $deleted,
            ]);
        }

        return redirect()
            ->route('portal.transactions', $request->only(['month', 'period']))
            ->with('success', $message);
    }
}
