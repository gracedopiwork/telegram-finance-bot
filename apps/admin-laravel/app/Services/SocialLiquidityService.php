<?php

namespace App\Services;

use App\Models\BotSocialReceivable;
use App\Models\BotTransaction;
use App\Support\TransactionTaxonomy;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SocialLiquidityService
{
    /**
     * Setelah transaksi Piutang Keluar/Masuk tersimpan.
     */
    public function syncFromTransaction(BotTransaction $transaction): void
    {
        if ($transaction->type === TransactionTaxonomy::TYPE_RECEIVABLE_OUT) {
            $this->openFromOutbound($transaction);

            return;
        }

        if ($transaction->type === TransactionTaxonomy::TYPE_RECEIVABLE_IN) {
            $this->settleFromInbound($transaction);
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

        return BotSocialReceivable::query()->create([
            'telegram_user_id' => (int) $transaction->telegram_user_id,
            'outbound_transaction_id' => $transaction->id,
            'counterparty_name' => $this->extractCounterparty((string) $transaction->notes, (string) $transaction->sub_category),
            'amount' => (int) $transaction->amount,
            'expected_back_at' => null,
            'status' => BotSocialReceivable::STATUS_ACTIVE,
            'mood_at_lend' => $transaction->mood,
        ]);
    }

    public function settleFromInbound(BotTransaction $transaction): ?BotSocialReceivable
    {
        $name = $this->extractCounterparty((string) $transaction->notes, (string) $transaction->sub_category);
        $amount = (int) $transaction->amount;
        $userId = (int) $transaction->telegram_user_id;

        $query = BotSocialReceivable::query()
            ->forUser($userId)
            ->active()
            ->where('amount', $amount)
            ->orderBy('created_at');

        if ($name !== '') {
            $match = (clone $query)
                ->whereRaw('LOWER(counterparty_name) = ?', [mb_strtolower($name)])
                ->first();
            if ($match !== null) {
                return $this->markSettled($match, $transaction);
            }
        }

        $match = $query->first();
        if ($match === null) {
            return null;
        }

        return $this->markSettled($match, $transaction);
    }

    public function markSettled(BotSocialReceivable $receivable, BotTransaction $inbound): BotSocialReceivable
    {
        $receivable->update([
            'status' => BotSocialReceivable::STATUS_SETTLED,
            'settled_transaction_id' => $inbound->id,
        ]);

        return $receivable->fresh();
    }

    /**
     * Relakan → status written_off (konversi ke Pengeluaran dilakukan di controller terpisah).
     */
    public function writeOff(BotSocialReceivable $receivable): BotSocialReceivable
    {
        $receivable->update(['status' => BotSocialReceivable::STATUS_WRITTEN_OFF]);

        return $receivable->fresh();
    }

    /**
     * @return array{
     *   outbound_month: int,
     *   outbound_share: float,
     *   repaid_month: int,
     *   repaid_share_of_outbound: float,
     *   active_total: int,
     *   written_off_month: int,
     *   status: string,
     *   status_label: string,
     *   count_outbound: int,
     *   count_active: int
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

        $activeTotal = (int) BotSocialReceivable::query()
            ->forUser($telegramUserId)
            ->active()
            ->sum('amount');
        $countActive = BotSocialReceivable::query()
            ->forUser($telegramUserId)
            ->active()
            ->count();

        $writtenOffMonth = (int) BotSocialReceivable::query()
            ->forUser($telegramUserId)
            ->where('status', BotSocialReceivable::STATUS_WRITTEN_OFF)
            ->whereBetween('updated_at', [$periodStart, $periodEnd])
            ->sum('amount');

        $share = $income > 0 ? round(($outboundMonth / $income) * 100, 1) : 0.0;
        $repaidShare = $outboundMonth > 0 ? round(($repaidMonth / $outboundMonth) * 100, 1) : 0.0;

        [$status, $label] = $this->statusFromShare($share, $activeTotal, $outboundMonth, $countOutbound);

        return [
            'outbound_month' => $outboundMonth,
            'outbound_share' => $share,
            'repaid_month' => $repaidMonth,
            'repaid_share_of_outbound' => $repaidShare,
            'active_total' => $activeTotal,
            'written_off_month' => $writtenOffMonth,
            'status' => $status,
            'status_label' => $label,
            'count_outbound' => $countOutbound,
            'count_active' => $countActive,
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function statusFromShare(float $share, int $activeTotal, int $outboundMonth, int $countOutbound): array
    {
        if ($countOutbound === 0 && $activeTotal === 0) {
            return ['empty', 'Belum ada pencatatan piutang di periode ini'];
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

        if ($activeTotal > 0) {
            return [
                'ok',
                'Arus likuiditas sosial terkendali. Ada piutang aktif yang masih menunggu kembali.',
            ];
        }

        return [
            'ok',
            'Arus likuiditas sosial terkendali. Tidak ada dampak signifikan ke budget bulan ini.',
        ];
    }

    public function extractCounterparty(string $notes, string $subCategory = ''): string
    {
        $sub = trim($subCategory);
        if ($sub !== '' && $sub !== '-') {
            return mb_substr($sub, 0, 120);
        }

        $patterns = [
            '/\b(?:ke|kepada|sama)\s+([A-Za-zÀ-ÿ][\wÀ-ÿ.\-]{1,40})/iu',
            '/\b(?:dari|oleh)\s+([A-Za-zÀ-ÿ][\wÀ-ÿ.\-]{1,40})/iu',
            '/\bpinjam(?:in|i|kan)?\s+([A-Za-zÀ-ÿ][\wÀ-ÿ.\-]{1,40})/iu',
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $notes, $m)) {
                $name = trim($m[1]);
                $skip = ['saya', 'aku', 'dia', 'teman', 'saudara', 'keluarga', 'orang', 'dulu', 'nanti'];
                if ($name !== '' && ! in_array(mb_strtolower($name), $skip, true)) {
                    return mb_substr($name, 0, 120);
                }
            }
        }

        return '';
    }
}
