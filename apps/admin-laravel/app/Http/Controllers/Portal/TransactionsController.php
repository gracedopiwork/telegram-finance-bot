<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\BotTransaction;
use App\Services\TransactionImportService;
use App\Support\PortalSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionsController extends Controller
{
    public function importTemplate(): StreamedResponse
    {
        $csv = app(TransactionImportService::class)->templateCsv();

        return response()->streamDownload(function () use ($csv): void {
            echo $csv;
        }, 'yfd-template-transaksi.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
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

    public function destroy(Request $request, BotTransaction $transaction): RedirectResponse
    {
        $telegramUserId = (int) PortalSession::telegramUserId($request);
        if ($telegramUserId <= 0) {
            return redirect()
                ->route('portal.login')
                ->with('warning', 'Sesi portal habis. Silakan login ulang.');
        }

        if ((int) $transaction->telegram_user_id !== $telegramUserId) {
            return redirect()
                ->route('portal.transactions', $request->only(['month', 'period']))
                ->with('warning', 'Transaksi tidak ditemukan atau bukan milik akun Anda.');
        }

        $transaction->delete();

        return redirect()
            ->route('portal.transactions', $request->only(['month', 'period']))
            ->with('success', 'Transaksi berhasil dihapus.');
    }
}
