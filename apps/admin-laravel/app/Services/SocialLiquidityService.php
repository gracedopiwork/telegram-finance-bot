<?php

namespace App\Services;

use App\Models\BotSocialPayable;
use App\Models\BotSocialReceivable;
use App\Models\BotTransaction;
use App\Support\TransactionTaxonomy;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SocialLiquidityService
{
    /**
     * Setelah transaksi Piutang/Utang sosial tersimpan.
     */
    public function syncFromTransaction(BotTransaction $transaction): void
    {
        if ($transaction->type === TransactionTaxonomy::TYPE_RECEIVABLE_OUT) {
            $this->openFromOutbound($transaction);

            return;
        }

        if ($transaction->type === TransactionTaxonomy::TYPE_RECEIVABLE_IN) {
            $this->settleFromInbound($transaction);

            return;
        }

        if ($transaction->type === TransactionTaxonomy::TYPE_PAYABLE_IN) {
            $this->openFromBorrow($transaction);

            return;
        }

        if ($transaction->type === TransactionTaxonomy::TYPE_PAYABLE_OUT) {
            $this->settleFromRepay($transaction);
        }
    }

    /**
     * Override purpose / jatuh tempo dari meta bot (jika tersedia).
     */
    public function applyBotMeta(BotTransaction $transaction, ?string $purpose, ?string $expectedBackAt): void
    {
        $updates = [];
        if ($purpose !== null && trim($purpose) !== '') {
            $updates['purpose'] = mb_substr(trim($purpose), 0, 180);
        }
        if ($expectedBackAt !== null && trim($expectedBackAt) !== '') {
            try {
                $updates['expected_back_at'] = Carbon::parse($expectedBackAt)->startOfDay();
            } catch (\Throwable) {
                // keep existing
            }
        }
        if ($updates === []) {
            return;
        }

        if ($transaction->type === TransactionTaxonomy::TYPE_RECEIVABLE_OUT) {
            BotSocialReceivable::query()
                ->where('outbound_transaction_id', $transaction->id)
                ->update($updates);

            return;
        }

        if ($transaction->type === TransactionTaxonomy::TYPE_PAYABLE_IN) {
            BotSocialPayable::query()
                ->where('inbound_transaction_id', $transaction->id)
                ->update($updates);
        }
    }

    public function openFromOutbound(BotTransaction $transaction): BotSocialReceivable
    {
        $existing = BotSocialReceivable::query()
            ->where('outbound_transaction_id', $transaction->id)
            ->first();
        if ($existing !== null) {
            return $existing;
        }

        $notes = (string) $transaction->notes;
        $amount = (int) $transaction->amount;
        $recorded = $transaction->recorded_at?->copy() ?? now();

        return BotSocialReceivable::query()->create([
            'telegram_user_id' => (int) $transaction->telegram_user_id,
            'outbound_transaction_id' => $transaction->id,
            'counterparty_name' => $this->extractCounterparty($notes, (string) $transaction->sub_category),
            'purpose' => $this->extractPurpose($notes),
            'amount' => $amount,
            'amount_remaining' => $amount,
            'expected_back_at' => $this->resolveExpectedBackAt($notes, $amount, $recorded),
            'status' => BotSocialReceivable::STATUS_ACTIVE,
            'mood_at_lend' => $transaction->mood,
        ]);
    }

    public function settleFromInbound(BotTransaction $transaction): ?BotSocialReceivable
    {
        $match = $this->findActiveReceivable($transaction);
        if ($match === null) {
            return null;
        }

        return $this->markReceivableSettled($match, $transaction);
    }

    public function openFromBorrow(BotTransaction $transaction): BotSocialPayable
    {
        $existing = BotSocialPayable::query()
            ->where('inbound_transaction_id', $transaction->id)
            ->first();
        if ($existing !== null) {
            return $existing;
        }

        $notes = (string) $transaction->notes;
        $amount = (int) $transaction->amount;
        $recorded = $transaction->recorded_at?->copy() ?? now();

        return BotSocialPayable::query()->create([
            'telegram_user_id' => (int) $transaction->telegram_user_id,
            'inbound_transaction_id' => $transaction->id,
            'counterparty_name' => $this->extractCounterparty($notes, (string) $transaction->sub_category),
            'purpose' => $this->extractPurpose($notes),
            'amount' => $amount,
            'amount_remaining' => $amount,
            'expected_back_at' => $this->resolveExpectedBackAt($notes, $amount, $recorded),
            'status' => BotSocialPayable::STATUS_ACTIVE,
            'mood_at_borrow' => $transaction->mood,
        ]);
    }

    public function settleFromRepay(BotTransaction $transaction): ?BotSocialPayable
    {
        $match = $this->findActivePayable($transaction);
        if ($match === null) {
            return null;
        }

        return $this->markPayableSettled($match, $transaction);
    }

    public function markReceivableSettled(BotSocialReceivable $receivable, BotTransaction $inbound): BotSocialReceivable
    {
        $pay = (int) $inbound->amount;
        $remaining = $receivable->remainingAmount();
        $newRemaining = max(0, $remaining - $pay);

        $updates = [
            'amount_remaining' => $newRemaining,
            'settled_transaction_id' => $inbound->id,
        ];
        if ($newRemaining <= 0) {
            $updates['status'] = BotSocialReceivable::STATUS_SETTLED;
        }

        $receivable->update($updates);

        return $receivable->fresh();
    }

    /** @deprecated Use markReceivableSettled */
    public function markSettled(BotSocialReceivable $receivable, BotTransaction $inbound): BotSocialReceivable
    {
        return $this->markReceivableSettled($receivable, $inbound);
    }

    public function markPayableSettled(BotSocialPayable $payable, BotTransaction $repay): BotSocialPayable
    {
        $pay = (int) $repay->amount;
        $remaining = $payable->remainingAmount();
        $newRemaining = max(0, $remaining - $pay);

        $updates = [
            'amount_remaining' => $newRemaining,
            'settled_transaction_id' => $repay->id,
        ];
        if ($newRemaining <= 0) {
            $updates['status'] = BotSocialPayable::STATUS_SETTLED;
        }

        $payable->update($updates);

        return $payable->fresh();
    }

    /**
     * Relakan → written_off + catat Pengeluaran Sosial & Keluarga (Flexible).
     */
    public function writeOff(BotSocialReceivable $receivable): BotSocialReceivable
    {
        if ($receivable->status !== BotSocialReceivable::STATUS_ACTIVE) {
            return $receivable;
        }

        $name = trim((string) $receivable->counterparty_name) ?: 'teman';
        $purpose = trim((string) $receivable->purpose);
        $note = 'Direlakan: piutang ke '.$name.($purpose !== '' ? ' ('.$purpose.')' : '');
        $writeOffAmount = $receivable->remainingAmount();

        BotTransaction::query()->create([
            'telegram_user_id' => (int) $receivable->telegram_user_id,
            'recorded_at' => now(),
            'type' => TransactionTaxonomy::TYPE_EXPENSE,
            'category' => 'Sosial & Keluarga',
            'sub_category' => $name,
            'amount' => max(1, $writeOffAmount),
            'nature' => 'Wants',
            'mood' => $receivable->mood_at_lend ?: 'Neutral',
            'is_impulsive' => false,
            'notes' => $note,
            'source' => 'manual',
        ]);

        $receivable->update([
            'status' => BotSocialReceivable::STATUS_WRITTEN_OFF,
            'amount_remaining' => 0,
        ]);

        return $receivable->fresh();
    }

    public function markReceivableDisputed(BotSocialReceivable $receivable): BotSocialReceivable
    {
        if ($receivable->status !== BotSocialReceivable::STATUS_ACTIVE) {
            return $receivable;
        }

        $receivable->update(['status' => BotSocialReceivable::STATUS_DISPUTED]);

        return $receivable->fresh();
    }

    public function markPayableDisputed(BotSocialPayable $payable): BotSocialPayable
    {
        if ($payable->status !== BotSocialPayable::STATUS_ACTIVE) {
            return $payable;
        }

        $payable->update(['status' => BotSocialPayable::STATUS_DISPUTED]);

        return $payable->fresh();
    }

    /**
     * Hapus baris tracker. Jika masih aktif, transaksi buka (Piutang Keluar / Utang Masuk) ikut dihapus.
     */
    public function deleteReceivable(BotSocialReceivable $receivable): void
    {
        $wasActive = $receivable->status === BotSocialReceivable::STATUS_ACTIVE;
        $outboundId = $receivable->outbound_transaction_id;
        $receivable->delete();

        if ($wasActive && $outboundId) {
            BotTransaction::query()->where('id', $outboundId)->delete();
        }
    }

    public function deletePayable(BotSocialPayable $payable): void
    {
        $wasActive = $payable->status === BotSocialPayable::STATUS_ACTIVE;
        $inboundId = $payable->inbound_transaction_id;
        $payable->delete();

        if ($wasActive && $inboundId) {
            BotTransaction::query()->where('id', $inboundId)->delete();
        }
    }

    /**
     * @return array{
     *   outbound_month: int,
     *   outbound_share: float,
     *   repaid_month: int,
     *   repaid_share_of_outbound: float,
     *   active_total: int,
     *   written_off_month: int,
     *   overdue_receivable_total: int,
     *   overdue_payable_total: int,
     *   status: string,
     *   status_label: string,
     *   count_outbound: int,
     *   count_active: int,
     *   borrow_month: int,
     *   repay_debt_month: int,
     *   active_debt_total: int,
     *   count_active_debt: int,
     *   tracker_receivables: list<array<string, mixed>>,
     *   tracker_payables: list<array<string, mixed>>
     * }
     */
    public function dashboardSummary(int $telegramUserId, Collection $periodRows, int $income, Carbon $periodStart, Carbon $periodEnd): array
    {
        $outboundMonth = (int) $periodRows
            ->where('type', TransactionTaxonomy::TYPE_RECEIVABLE_OUT)
            ->sum('amount');
        $repaidMonth = (int) $periodRows
            ->where('type', TransactionTaxonomy::TYPE_RECEIVABLE_IN)
            ->sum('amount');
        $countOutbound = $periodRows->where('type', TransactionTaxonomy::TYPE_RECEIVABLE_OUT)->count();

        $borrowMonth = (int) $periodRows
            ->where('type', TransactionTaxonomy::TYPE_PAYABLE_IN)
            ->sum('amount');
        $repayDebtMonth = (int) $periodRows
            ->where('type', TransactionTaxonomy::TYPE_PAYABLE_OUT)
            ->sum('amount');
        $countBorrow = $periodRows->where('type', TransactionTaxonomy::TYPE_PAYABLE_IN)->count();

        $activeTotal = (int) BotSocialReceivable::query()
            ->forUser($telegramUserId)
            ->active()
            ->get()
            ->sum(fn (BotSocialReceivable $row) => $row->remainingAmount());
        $countActive = BotSocialReceivable::query()
            ->forUser($telegramUserId)
            ->active()
            ->count();

        $activeDebtTotal = (int) BotSocialPayable::query()
            ->forUser($telegramUserId)
            ->active()
            ->get()
            ->sum(fn (BotSocialPayable $row) => $row->remainingAmount());
        $countActiveDebt = BotSocialPayable::query()
            ->forUser($telegramUserId)
            ->active()
            ->count();

        $writtenOffMonth = (int) BotSocialReceivable::query()
            ->forUser($telegramUserId)
            ->where('status', BotSocialReceivable::STATUS_WRITTEN_OFF)
            ->whereBetween('updated_at', [$periodStart, $periodEnd])
            ->sum('amount');

        $overdueReceivableTotal = (int) BotSocialReceivable::query()
            ->forUser($telegramUserId)
            ->active()
            ->whereNotNull('expected_back_at')
            ->where('expected_back_at', '<', now())
            ->get()
            ->sum(fn (BotSocialReceivable $row) => $row->remainingAmount());
        $overduePayableTotal = (int) BotSocialPayable::query()
            ->forUser($telegramUserId)
            ->active()
            ->whereNotNull('expected_back_at')
            ->where('expected_back_at', '<', now())
            ->get()
            ->sum(fn (BotSocialPayable $row) => $row->remainingAmount());

        $share = $income > 0 ? round(($outboundMonth / $income) * 100, 1) : 0.0;
        $repaidShare = $outboundMonth > 0 ? round(($repaidMonth / $outboundMonth) * 100, 1) : 0.0;

        [$status, $label] = $this->statusFromShare(
            $share,
            $activeTotal,
            $outboundMonth,
            $countOutbound,
            $borrowMonth,
            $activeDebtTotal,
            $countBorrow,
            $overdueReceivableTotal,
            $overduePayableTotal,
        );

        return [
            'outbound_month' => $outboundMonth,
            'outbound_share' => $share,
            'repaid_month' => $repaidMonth,
            'repaid_share_of_outbound' => $repaidShare,
            'active_total' => $activeTotal,
            'written_off_month' => $writtenOffMonth,
            'overdue_receivable_total' => $overdueReceivableTotal,
            'overdue_payable_total' => $overduePayableTotal,
            'status' => $status,
            'status_label' => $label,
            'count_outbound' => $countOutbound,
            'count_active' => $countActive,
            'borrow_month' => $borrowMonth,
            'repay_debt_month' => $repayDebtMonth,
            'active_debt_total' => $activeDebtTotal,
            'count_active_debt' => $countActiveDebt,
            'tracker_receivables' => $this->trackerReceivables($telegramUserId),
            'tracker_payables' => $this->trackerPayables($telegramUserId),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function trackerReceivables(int $telegramUserId, int $limit = 40): array
    {
        return BotSocialReceivable::query()
            ->forUser($telegramUserId)
            ->whereIn('status', [
                BotSocialReceivable::STATUS_ACTIVE,
                BotSocialReceivable::STATUS_SETTLED,
                BotSocialReceivable::STATUS_WRITTEN_OFF,
                BotSocialReceivable::STATUS_DISPUTED,
            ])
            ->orderByRaw("CASE status WHEN 'active' THEN 0 WHEN 'disputed' THEN 1 WHEN 'settled' THEN 2 ELSE 3 END")
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (BotSocialReceivable $row) => $this->formatTrackerRow($row, 'receivable'))
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function trackerPayables(int $telegramUserId, int $limit = 40): array
    {
        return BotSocialPayable::query()
            ->forUser($telegramUserId)
            ->whereIn('status', [
                BotSocialPayable::STATUS_ACTIVE,
                BotSocialPayable::STATUS_SETTLED,
                BotSocialPayable::STATUS_DISPUTED,
            ])
            ->orderByRaw("CASE status WHEN 'active' THEN 0 WHEN 'disputed' THEN 1 WHEN 'settled' THEN 2 ELSE 3 END")
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (BotSocialPayable $row) => $this->formatTrackerRow($row, 'payable'))
            ->all();
    }

    /**
     * @return Collection<int, BotSocialReceivable>
     */
    public function dueReceivablesForNotify(?Carbon $asOf = null): Collection
    {
        $asOf ??= now();

        return BotSocialReceivable::query()
            ->active()
            ->whereNotNull('expected_back_at')
            ->where('expected_back_at', '<=', $asOf->copy()->endOfDay())
            ->where(function ($q) use ($asOf): void {
                $q->whereNull('due_notified_at')
                    ->orWhere('due_notified_at', '<', $asOf->copy()->subDay()->startOfDay());
            })
            ->orderBy('expected_back_at')
            ->get();
    }

    /**
     * @return Collection<int, BotSocialPayable>
     */
    public function duePayablesForNotify(?Carbon $asOf = null): Collection
    {
        $asOf ??= now();

        return BotSocialPayable::query()
            ->active()
            ->whereNotNull('expected_back_at')
            ->where('expected_back_at', '<=', $asOf->copy()->endOfDay())
            ->where(function ($q) use ($asOf): void {
                $q->whereNull('due_notified_at')
                    ->orWhere('due_notified_at', '<', $asOf->copy()->subDay()->startOfDay());
            })
            ->orderBy('expected_back_at')
            ->get();
    }

    public function extractCounterparty(string $notes, string $subCategory = ''): string
    {
        $skip = [
            'saya', 'aku', 'dia', 'teman', 'saudara', 'keluarga', 'orang', 'dulu', 'nanti',
            'besok', 'lusa', 'buat', 'untuk', 'uang', 'duit', 'balik', 'balikin', 'kembali',
            'transfer', 'hutang', 'utang', 'pinjaman', 'piutang', 'masuk', 'keluar',
            'dokter', 'rs', 'rumahsakit', 'rumah', 'sakit', 'apotek', 'klinik', 'puskesmas',
            'sekolah', 'kuliah', 'kampus', 'universitas', 'biaya', 'kebutuhan', 'kepentingan',
            'keperluan', 'kerja', 'bisnis', 'obat', 'lab', 'igd', 'ugd', 'kantor', 'bank',
            'kos', 'bulan', 'tanggal', 'tgl', 'ini', 'itu',
        ];

        $sub = trim($subCategory);
        if ($sub !== '' && $sub !== '-' && ! in_array(mb_strtolower($sub), $skip, true)) {
            return mb_substr($sub, 0, 120);
        }

        $patterns = [
            '/\bmeminjamkan\s+(?:(?:duit|uang|dana)\s+)?(?:kepada|ke|sama)?\s*([A-Za-zÀ-ÿ][\wÀ-ÿ.\-]{1,40})/iu',
            '/\b(?:di\s*pinjam|dipinjam|dipinjami|pinjamin|pinjami|pinjamkan|ngutangin|ngutangi|utangin|hutangin)\s+([A-Za-zÀ-ÿ][\wÀ-ÿ.\-]{1,40})/iu',
            '/\b(?:pinjam|utang|hutang|ngutang|minjem)\s+(?:dari|ke|kepada|sama)\s+([A-Za-zÀ-ÿ][\wÀ-ÿ.\-]{1,40})/iu',
            '/\b(?:pinjam|pinjem|minjem)\s+(?:duit|uang|dana)\s+([A-Za-zÀ-ÿ][\wÀ-ÿ.\-]{1,40})/iu',
            '/\b(?:transfer|bantu|talangin|bayarkan)\s+(?:ke|kepada|sama)?\s*([A-Za-zÀ-ÿ][\wÀ-ÿ.\-]{1,40})/iu',
            '/\b(?:kepada|sama|dari|oleh)\s+([A-Za-zÀ-ÿ][\wÀ-ÿ.\-]{1,40})/iu',
            '/\bke\s+([A-Za-zÀ-ÿ][\wÀ-ÿ.\-]{1,40})/iu',
        ];
        foreach ($patterns as $pattern) {
            if (! preg_match_all($pattern, $notes, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
                continue;
            }
            foreach ($matches as $m) {
                $name = trim($m[1][0], " .,");
                if ($name === '' || in_array(mb_strtolower($name), $skip, true) || ctype_digit($name)) {
                    continue;
                }
                $bytePos = (int) $m[0][1];
                $prefix = mb_strtolower(substr($notes, max(0, $bytePos - 48), min(48, $bytePos)));
                if (preg_match('/(?:untuk|buat|biaya|keperluan|kepentingan|tujuan)\s+(?:ke\s+)?$/u', $prefix)) {
                    continue;
                }

                return mb_substr($name, 0, 120);
            }
        }

        return '';
    }

    public function extractPurpose(string $notes): string
    {
        if (preg_match(
            '/\b(?:buat|untuk|kepentingan|keperluan|tujuan)\s+(.+?)(?:\.\s*|,\s*|\s+besok|\s+lusa|\s+nanti|\s+minggu|\s+bulan|\s+tanggal|\s+di\s+transfer|\s+transfer|\s+kembali|\s*\||$)/iu',
            $notes,
            $m
        )) {
            $purpose = trim(preg_replace('/\s+/u', ' ', $m[1]) ?? '', " .,");
            if (mb_strlen($purpose) >= 3) {
                return mb_substr($purpose, 0, 180);
            }
        }

        return '';
    }

    public function resolveExpectedBackAt(string $notes, int $amount, Carbon $base): Carbon
    {
        $days = $this->matchRelativeDueDays($notes);
        if ($days !== null) {
            return $base->copy()->startOfDay()->addDays($days);
        }

        if (preg_match('/\b(?:tgl|tanggal)\s*(\d{1,2})(?:[\/\-.](\d{1,2})(?:[\/\-.](\d{2,4}))?)?\b/iu', $notes, $m)) {
            $day = (int) $m[1];
            $month = isset($m[2]) ? (int) $m[2] : $base->month;
            $year = isset($m[3]) ? (int) $m[3] : $base->year;
            if ($year < 100) {
                $year += 2000;
            }
            try {
                return Carbon::create($year, $month, $day, 0, 0, 0, $base->timezone)?->startOfDay() ?? $this->defaultExpectedBackAt($amount, $base);
            } catch (\Throwable) {
                // fall through
            }
        }

        return $this->defaultExpectedBackAt($amount, $base);
    }

    public function defaultDueDays(int $amount): int
    {
        if ($amount < 500_000) {
            return 30;
        }
        if ($amount <= 2_000_000) {
            return 60;
        }

        return 90;
    }

    /**
     * Baca frasa waktu dari catatan bot ("balikin besok", "sebulan ke depan", typo "bulan depam").
     */
    public function matchRelativeDueDays(string $notes): ?int
    {
        $lower = preg_replace('/\s+/u', ' ', mb_strtolower($notes)) ?? '';
        $lower = str_replace(['kedepan', 'ke-depan'], 'ke depan', $lower);
        $lower = preg_replace('/\bbln\b/u', 'bulan', $lower) ?? $lower;
        $lower = preg_replace('/\bmnggu\b/u', 'minggu', $lower) ?? $lower;
        $lower = preg_replace('/\bmingu\b/u', 'minggu', $lower) ?? $lower;

        $relative = [
            'sebulan ke depan' => 30,
            'sebulan kedepan' => 30,
            'satu bulan ke depan' => 30,
            '1 bulan ke depan' => 30,
            'bulan ke depan' => 30,
            'bulan kedepan' => 30,
            'bulan depan' => 30,
            'bulan depam' => 30,
            'bulan depa' => 30,
            'bulan depn' => 30,
            'minggu depan' => 7,
            'dua minggu' => 14,
            '2 minggu' => 14,
            '30 hari' => 30,
            '60 hari' => 60,
            '90 hari' => 90,
            'besok' => 1,
            'lusa' => 2,
        ];
        foreach ($relative as $needle => $days) {
            if (str_contains($lower, $needle)) {
                return $days;
            }
        }

        if (preg_match('/\b(sebulan|bulan)\s+(depam|depa|depn|depann|kedepan)\b/u', $lower)) {
            return 30;
        }
        if (preg_match('/\bsebulan\b/u', $lower) && preg_match('/\b(depan|lagi|ke)\b/u', $lower)) {
            return 30;
        }

        return null;
    }

    private function defaultExpectedBackAt(int $amount, Carbon $base): Carbon
    {
        return $base->copy()->startOfDay()->addDays($this->defaultDueDays($amount));
    }

    private function findActiveReceivable(BotTransaction $transaction): ?BotSocialReceivable
    {
        $name = $this->extractCounterparty((string) $transaction->notes, (string) $transaction->sub_category);
        $amount = (int) $transaction->amount;
        $userId = (int) $transaction->telegram_user_id;

        $query = BotSocialReceivable::query()
            ->forUser($userId)
            ->active()
            ->orderBy('created_at');

        if ($name !== '') {
            $match = (clone $query)
                ->whereRaw('LOWER(counterparty_name) = ?', [mb_strtolower($name)])
                ->get()
                ->first(fn (BotSocialReceivable $row) => $row->remainingAmount() > 0);
            if ($match !== null) {
                return $match;
            }
        }

        $withRoom = (clone $query)->get()
            ->first(fn (BotSocialReceivable $row) => $row->remainingAmount() >= $amount);
        if ($withRoom !== null) {
            return $withRoom;
        }

        return $query->get()->first(fn (BotSocialReceivable $row) => $row->remainingAmount() > 0);
    }

    private function findActivePayable(BotTransaction $transaction): ?BotSocialPayable
    {
        $name = $this->extractCounterparty((string) $transaction->notes, (string) $transaction->sub_category);
        $amount = (int) $transaction->amount;
        $userId = (int) $transaction->telegram_user_id;

        $query = BotSocialPayable::query()
            ->forUser($userId)
            ->active()
            ->orderBy('created_at');

        if ($name !== '') {
            $match = (clone $query)
                ->whereRaw('LOWER(counterparty_name) = ?', [mb_strtolower($name)])
                ->get()
                ->first(fn (BotSocialPayable $row) => $row->remainingAmount() > 0);
            if ($match !== null) {
                return $match;
            }
        }

        $withRoom = (clone $query)->get()
            ->first(fn (BotSocialPayable $row) => $row->remainingAmount() >= $amount);
        if ($withRoom !== null) {
            return $withRoom;
        }

        return $query->get()->first(fn (BotSocialPayable $row) => $row->remainingAmount() > 0);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatTrackerRow(BotSocialReceivable|BotSocialPayable $row, string $kind): array
    {
        $due = $row->expected_back_at;
        $isActive = $row->status === 'active';
        $isOverdue = $isActive && $due !== null && $due->isPast();
        $original = (int) $row->amount;
        $remaining = $row->remainingAmount();
        $repaid = max(0, $original - $remaining);
        $isPartial = $isActive && $repaid > 0 && $remaining > 0;

        $statusLabel = match ($row->status) {
            'settled' => 'Lunas',
            'written_off' => 'Direlakan',
            'disputed' => 'Sengketa',
            default => $isPartial
                ? 'Cicilan'
                : ($isOverdue ? 'Jatuh tempo' : 'Aktif'),
        };

        $followUp = match ($row->status) {
            'settled' => $kind === 'receivable' ? 'Piutang ditutup' : 'Utang ditutup',
            'written_off' => 'Masuk Sosial & Keluarga',
            'disputed' => 'Di luar perhitungan',
            default => $isPartial
                ? ('Sisa Rp'.number_format($remaining, 0, ',', '.').' — menunggu cicilan berikutnya')
                : ($isOverdue
                    ? ($kind === 'receivable' ? 'Saatnya ditagih' : 'Saatnya dibayar')
                    : 'Menunggu'),
        };

        $dueLabel = $due ? $due->timezone(config('app.timezone', 'Asia/Jakarta'))->format('j/n/Y') : '—';

        return [
            'id' => (int) $row->id,
            'kind' => $kind,
            'name' => trim((string) $row->counterparty_name) !== '' ? (string) $row->counterparty_name : '—',
            'amount' => $original,
            'amount_remaining' => $remaining,
            'amount_repaid' => $repaid,
            'is_partial' => $isPartial,
            'purpose' => trim((string) ($row->purpose ?? '')) !== '' ? (string) $row->purpose : '—',
            'status' => (string) $row->status,
            'status_label' => $statusLabel,
            'is_overdue' => $isOverdue,
            'due_label' => $dueLabel,
            'follow_up' => $followUp,
            'can_write_off' => $kind === 'receivable' && $isActive,
            'can_dispute' => $isActive,
            'can_delete' => true,
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function statusFromShare(
        float $share,
        int $activeTotal,
        int $outboundMonth,
        int $countOutbound,
        int $borrowMonth = 0,
        int $activeDebtTotal = 0,
        int $countBorrow = 0,
        int $overdueReceivableTotal = 0,
        int $overduePayableTotal = 0,
    ): array {
        if ($countOutbound === 0 && $activeTotal === 0 && $countBorrow === 0 && $activeDebtTotal === 0) {
            return ['empty', 'Belum ada pencatatan piutang/utang sosial di periode ini'];
        }

        if ($overdueReceivableTotal > 0 || $overduePayableTotal > 0) {
            return [
                'watch',
                'Ada piutang/utang jatuh tempo. Cek tracker di bawah untuk ditagih atau dibayar.',
            ];
        }

        if ($share > 20.0) {
            return [
                'critical',
                'Likuiditas sosial melebihi 20% income. Pantau dampak ke Future Building.',
            ];
        }

        if ($share >= 10.0) {
            return [
                'watch',
                'Likuiditas sosial cukup besar bulan ini. Pantau apakah mulai mempengaruhi Future Building.',
            ];
        }

        if ($activeTotal > 0 || $activeDebtTotal > 0) {
            return [
                'ok',
                'Arus likuiditas sosial terkendali. Ada piutang/utang aktif yang masih berjalan.',
            ];
        }

        return [
            'ok',
            'Arus likuiditas sosial terkendali. Tidak ada dampak signifikan ke budget bulan ini.',
        ];
    }
}
