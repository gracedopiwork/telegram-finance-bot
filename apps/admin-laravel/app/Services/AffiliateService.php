<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\AffiliateClaim;
use App\Models\AffiliateCommission;
use App\Models\CpDigitalProduct;
use App\Models\License;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AffiliateService
{
    public function enabled(): bool
    {
        return Setting::val('affiliate.enabled', '1') === '1';
    }

    public function discountAmount(): int
    {
        return max(0, (int) Setting::val('affiliate.discount_amount', '25000'));
    }

    public function commissionAmount(): int
    {
        return max(0, (int) Setting::val('affiliate.commission_amount', '25000'));
    }

    public function minClaimAmount(): int
    {
        return max(0, (int) Setting::val('affiliate.min_claim_amount', '25000'));
    }

    /** @return list<string> */
    public function eligibleProductCodes(): array
    {
        $raw = (string) Setting::val('affiliate.eligible_product_codes', 'yfd-bot-telegram');

        return array_values(array_filter(array_map(
            static fn (string $c) => strtolower(trim($c)),
            explode(',', $raw)
        )));
    }

    public function isEligibleProduct(?CpDigitalProduct $product, ?string $plan = null): bool
    {
        $code = strtolower(trim((string) ($product?->code ?: $plan)));
        if ($code === '') {
            return false;
        }

        return in_array($code, $this->eligibleProductCodes(), true);
    }

    public function taxPercent(?string $npwp): float
    {
        // Legacy: NPWP tidak lagi memengaruhi tarif. Default ke tarif individu.
        return $this->taxPercentForPayeeType(Affiliate::PAYEE_INDIVIDUAL);
    }

    public function taxPercentForPayeeType(?string $payeeType): float
    {
        $type = $this->normalizePayeeType($payeeType);
        $key = $type === Affiliate::PAYEE_CORPORATE
            ? 'affiliate.tax_corporate_percent'
            : 'affiliate.tax_individual_percent';
        $default = $type === Affiliate::PAYEE_CORPORATE ? '2' : '2.5';

        return max(0, (float) Setting::val($key, $default));
    }

    public function normalizePayeeType(?string $payeeType): string
    {
        $type = strtolower(trim((string) $payeeType));

        return $type === Affiliate::PAYEE_CORPORATE
            ? Affiliate::PAYEE_CORPORATE
            : Affiliate::PAYEE_INDIVIDUAL;
    }

    public function findActiveByCode(?string $code): ?Affiliate
    {
        $code = strtoupper(trim((string) $code));
        if ($code === '') {
            return null;
        }

        return Affiliate::query()
            ->where('referral_code', $code)
            ->where('is_active', true)
            ->first();
    }

    /**
     * @return array{affiliate: ?Affiliate, referral_code: ?string, referral_discount: int, final_amount: int, discount_amount: int}
     */
    public function applyCheckoutDiscount(
        CpDigitalProduct $product,
        int $baseAmount,
        ?string $referralCode,
        string $buyerEmail,
    ): array {
        $listPrice = (int) $product->price;
        $promoDiscount = max(0, $listPrice - $baseAmount);
        $result = [
            'affiliate' => null,
            'referral_code' => null,
            'referral_discount' => 0,
            'final_amount' => $baseAmount,
            'discount_amount' => $promoDiscount,
        ];

        if (! $this->enabled() || ! $this->isEligibleProduct($product)) {
            return $result;
        }

        $affiliate = $this->findActiveByCode($referralCode);
        if ($affiliate === null) {
            if (filled(trim((string) $referralCode))) {
                throw ValidationException::withMessages([
                    'referral_code' => 'Kode referral tidak valid atau nonaktif.',
                ]);
            }

            return $result;
        }

        if (strtolower($affiliate->email) === strtolower($buyerEmail)) {
            throw ValidationException::withMessages([
                'referral_code' => 'Tidak bisa memakai kode referral milik sendiri.',
            ]);
        }

        $referralDiscount = min($this->discountAmount(), $baseAmount);
        $final = max(0, $baseAmount - $referralDiscount);

        return [
            'affiliate' => $affiliate,
            'referral_code' => $affiliate->referral_code,
            'referral_discount' => $referralDiscount,
            'final_amount' => $final,
            'discount_amount' => $promoDiscount + $referralDiscount,
        ];
    }

    public function ensureForPortalUser(
        string $email,
        ?string $name = null,
        ?int $licenseId = null,
        ?string $preferredCode = null,
    ): Affiliate {
        $email = strtolower(trim($email));
        $preferredCode = $this->normalizeReferralCode($preferredCode);

        $affiliate = Affiliate::query()->where('email', $email)->first();

        if ($affiliate) {
            $dirty = false;
            if ($licenseId && ! $affiliate->license_id) {
                $affiliate->license_id = $licenseId;
                $dirty = true;
            }
            if ($name && ! $affiliate->name) {
                $affiliate->name = $name;
                $dirty = true;
            }
            if ($preferredCode !== null && $preferredCode !== $affiliate->referral_code) {
                $this->assertReferralCodeAvailable($preferredCode, $affiliate->id);
                $affiliate->referral_code = $preferredCode;
                $dirty = true;
            }
            if ($dirty) {
                $affiliate->save();
            }

            return $affiliate;
        }

        $code = $preferredCode ?? $this->generateUniqueCode();
        if ($preferredCode !== null) {
            $this->assertReferralCodeAvailable($preferredCode);
        }

        return Affiliate::create([
            'email' => $email,
            'name' => $name,
            'license_id' => $licenseId,
            'referral_code' => $code,
            'is_active' => true,
        ]);
    }

    public function normalizeReferralCode(?string $code): ?string
    {
        $code = strtoupper(trim((string) $code));
        if ($code === '') {
            return null;
        }

        return $code;
    }

    public function assertReferralCodeAvailable(string $code, ?int $ignoreAffiliateId = null): void
    {
        $code = $this->normalizeReferralCode($code);
        if ($code === null) {
            throw ValidationException::withMessages([
                'referral_code' => 'Kode affiliate tidak boleh kosong.',
            ]);
        }

        if (! preg_match('/^[A-Z0-9][A-Z0-9\-]{2,31}$/', $code)) {
            throw ValidationException::withMessages([
                'referral_code' => 'Kode affiliate 3–32 karakter (huruf/angka/tanda -).',
            ]);
        }

        $exists = Affiliate::query()
            ->where('referral_code', $code)
            ->when($ignoreAffiliateId, fn ($q) => $q->where('id', '!=', $ignoreAffiliateId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'referral_code' => 'Kode affiliate sudah dipakai user lain.',
            ]);
        }
    }

    public function generateUniqueCode(): string
    {
        do {
            $code = 'YFD-'.Str::upper(Str::random(6));
        } while (Affiliate::query()->where('referral_code', $code)->exists());

        return $code;
    }

    public function suggestCodeFromName(string $name, ?int $ignoreAffiliateId = null): string
    {
        $ascii = Str::upper(Str::ascii(trim($name)));
        $slug = preg_replace('/[^A-Z0-9]+/', '-', $ascii) ?? '';
        $slug = trim($slug, '-');
        $parts = array_values(array_filter(explode('-', $slug)));
        $base = $parts[0] ?? 'USER';
        if (strlen($base) < 3 && isset($parts[1])) {
            $base = $base.$parts[1];
        }
        $base = substr($base, 0, 24);
        if ($base === '') {
            $base = 'USER';
        }

        $candidate = 'YFD-'.$base;
        $n = 2;
        while (
            Affiliate::query()
                ->where('referral_code', $candidate)
                ->when($ignoreAffiliateId, fn ($q) => $q->where('id', '!=', $ignoreAffiliateId))
                ->exists()
        ) {
            $suffix = (string) $n;
            $candidate = 'YFD-'.substr($base, 0, max(1, 24 - strlen($suffix))).$suffix;
            $n++;
            if ($n > 99) {
                return $this->generateUniqueCode();
            }
        }

        return $candidate;
    }

    public function creditCommissionForPaidOrder(Order $order): ?AffiliateCommission
    {
        if (! $this->enabled()) {
            return null;
        }

        if (! $order->affiliate_id) {
            return null;
        }

        $order->loadMissing('digitalProduct', 'affiliate');
        if (! $this->isEligibleProduct($order->digitalProduct, $order->plan)) {
            return null;
        }

        $existing = AffiliateCommission::query()->where('order_id', $order->id)->first();
        if ($existing) {
            return $existing;
        }

        $amount = $this->commissionAmount();
        if ($amount <= 0) {
            return null;
        }

        return AffiliateCommission::create([
            'affiliate_id' => $order->affiliate_id,
            'order_id' => $order->id,
            'amount' => $amount,
            'status' => 'available',
        ]);
    }

    public function submitClaim(
        Affiliate $affiliate,
        ?string $npwp = null,
        ?string $bankName = null,
        ?string $bankAccountNumber = null,
        ?string $bankAccountName = null,
        ?string $payeeType = null,
    ): AffiliateClaim
    {
        $available = $affiliate->commissions()->where('status', 'available')->get();
        $gross = (int) $available->sum('amount');
        $min = $this->minClaimAmount();

        if ($gross < $min) {
            throw ValidationException::withMessages([
                'claim' => "Saldo tersedia Rp ".number_format($gross, 0, ',', '.')." belum mencapai minimal klaim Rp ".number_format($min, 0, ',', '.').'.',
            ]);
        }

        if ($affiliate->claims()->where('status', 'pending')->exists()) {
            throw ValidationException::withMessages([
                'claim' => 'Masih ada klaim menunggu persetujuan admin.',
            ]);
        }

        $npwp = trim((string) ($npwp ?: $affiliate->npwp));
        $bankName = trim((string) ($bankName ?: $affiliate->bank_name));
        $bankAccountNumber = preg_replace('/\s+/', '', trim((string) ($bankAccountNumber ?: $affiliate->bank_account_number))) ?? '';
        $bankAccountName = trim((string) ($bankAccountName ?: $affiliate->bank_account_name));
        $payeeType = $this->normalizePayeeType($payeeType ?: $affiliate->payee_type);

        if ($bankName === '' || $bankAccountNumber === '' || $bankAccountName === '') {
            throw ValidationException::withMessages([
                'bank_account_number' => 'Lengkapi nama bank, nomor rekening, dan nama pemilik rekening untuk klaim.',
            ]);
        }

        $affiliate->npwp = $npwp !== '' ? $npwp : $affiliate->npwp;
        $affiliate->payee_type = $payeeType;
        $affiliate->bank_name = $bankName;
        $affiliate->bank_account_number = $bankAccountNumber;
        $affiliate->bank_account_name = $bankAccountName;
        $affiliate->save();

        $taxPercent = $this->taxPercentForPayeeType($payeeType);
        $taxAmount = (int) round($gross * ($taxPercent / 100));
        $net = max(0, $gross - $taxAmount);

        return DB::transaction(function () use (
            $affiliate,
            $available,
            $gross,
            $taxPercent,
            $taxAmount,
            $net,
            $npwp,
            $payeeType,
            $bankName,
            $bankAccountNumber,
            $bankAccountName,
        ) {
            $claim = AffiliateClaim::create([
                'affiliate_id' => $affiliate->id,
                'gross_amount' => $gross,
                'tax_percent' => $taxPercent,
                'tax_amount' => $taxAmount,
                'net_amount' => $net,
                'npwp_snapshot' => $npwp !== '' ? $npwp : null,
                'payee_type' => $payeeType,
                'bank_name' => $bankName,
                'bank_account_number' => $bankAccountNumber,
                'bank_account_name' => $bankAccountName,
                'status' => 'pending',
            ]);

            AffiliateCommission::query()
                ->whereIn('id', $available->pluck('id'))
                ->update([
                    'status' => 'claimed',
                    'claim_id' => $claim->id,
                ]);

            return $claim;
        });
    }

    public function processClaim(AffiliateClaim $claim, string $status, ?int $adminId, ?string $note = null): AffiliateClaim
    {
        if (! in_array($status, ['approved', 'paid', 'rejected'], true)) {
            throw new \InvalidArgumentException('Status klaim tidak valid.');
        }

        return DB::transaction(function () use ($claim, $status, $adminId, $note) {
            if ($status === 'rejected' && $claim->status === 'pending') {
                AffiliateCommission::query()
                    ->where('claim_id', $claim->id)
                    ->update([
                        'status' => 'available',
                        'claim_id' => null,
                    ]);
            }

            $claim->status = $status;
            $claim->admin_note = $note;
            $claim->processed_by = $adminId;
            $claim->processed_at = now();
            $claim->save();

            return $claim;
        });
    }

    public function shareUrl(Affiliate $affiliate): string
    {
        return route('checkout.show', [
            'code' => $this->eligibleProductCodes()[0] ?? 'yfd-bot-telegram',
            'ref' => $affiliate->referral_code,
        ]);
    }
}
