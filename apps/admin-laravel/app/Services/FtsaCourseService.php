<?php

namespace App\Services;

use App\Models\CpDigitalProduct;
use App\Models\FtsaCourse;
use App\Models\FtsaCourseRegistrant;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FtsaCourseService
{
    public const PRODUCT_CODE = 'yfd-ftsa-course';

    public function __construct(
        private readonly LicenseProvisioningService $licenses,
        private readonly PortalAccessService $portalAccess,
        private readonly LicenseEntitlementService $entitlements,
    ) {}

    public function findByCode(string $code): ?FtsaCourse
    {
        $code = $this->normalizeCode($code);
        if ($code === '') {
            return null;
        }

        return FtsaCourse::query()
            ->whereRaw('UPPER(registration_code) = ?', [$code])
            ->first();
    }

    public function normalizeCode(string $code): string
    {
        return strtoupper(trim($code));
    }

    public function generateRegistrationCode(string $companyName, ?\Carbon\CarbonInterface $workshopAt = null): string
    {
        $slug = strtoupper(preg_replace('/[^A-Z0-9]+/', '', Str::ascii(strtoupper($companyName))) ?? '');
        $slug = substr($slug !== '' ? $slug : 'YFD', 0, 8);
        $stamp = ($workshopAt ?? now())->format('my');
        $base = 'FTSA-'.$slug.'-'.$stamp;
        $code = $base;
        $i = 1;
        while (FtsaCourse::query()->where('registration_code', $code)->exists()) {
            $code = $base.'-'.$i;
            $i++;
        }

        return $code;
    }

    public function hasActiveAccessByEmail(string $email): bool
    {
        $email = strtolower(trim($email));
        if ($email === '') {
            return false;
        }

        return FtsaCourseRegistrant::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->where('access_ends_at', '>', now())
            ->exists();
    }

    public function hasRegistrationByEmail(string $email): bool
    {
        $email = strtolower(trim($email));
        if ($email === '') {
            return false;
        }

        return FtsaCourseRegistrant::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->exists();
    }

    /**
     * Course FTSA terkunci, tapi bisa dibuka lagi setelah First Aid aktif.
     */
    public function canReopenWithFirstAid(string $email): bool
    {
        $email = strtolower(trim($email));
        if ($email === '' || ! $this->hasRegistrationByEmail($email)) {
            return false;
        }

        return $this->entitlements->hasPaidBotOrderForEmail($email);
    }

    /**
     * @return array{registrant: FtsaCourseRegistrant, course: FtsaCourse, license_key: string, access_ends_at: string}
     */
    public function register(string $code, string $fullName, string $email, ?string $phone = null): array
    {
        $course = $this->findByCode($code);
        if ($course === null) {
            throw ValidationException::withMessages([
                'registration_code' => 'Kode daftar tidak ditemukan.',
            ]);
        }

        if (! $course->registrationWindowOpen()) {
            throw ValidationException::withMessages([
                'registration_code' => 'Kode daftar sudah kedaluwarsa atau course nonaktif. Masa akses course adalah 1 bulan dari tanggal workshop.',
            ]);
        }

        if (! $course->hasSeatAvailable()) {
            throw ValidationException::withMessages([
                'registration_code' => 'Kuota pendaftar untuk course ini sudah penuh.',
            ]);
        }

        $email = strtolower(trim($email));
        $fullName = trim($fullName);
        $phone = $phone !== null && trim($phone) !== '' ? trim($phone) : null;

        $existing = FtsaCourseRegistrant::query()
            ->where('ftsa_course_id', $course->id)
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if ($existing !== null) {
            $existing->loadMissing('license');

            return [
                'registrant' => $existing,
                'course' => $course,
                'license_key' => (string) ($existing->license?->license_key ?? ''),
                'access_ends_at' => $existing->access_ends_at?->timezone(config('portal.display_timezone', 'Asia/Jakarta'))->format('d M Y H:i') ?? '',
                'already_registered' => true,
            ];
        }

        $product = CpDigitalProduct::query()->where('code', self::PRODUCT_CODE)->first();
        if ($product === null) {
            throw ValidationException::withMessages([
                'registration_code' => 'Produk FTSA Course belum dikonfigurasi. Hubungi admin YFD.',
            ]);
        }

        $accessEndsAt = $course->accessEndsAt();

        $payload = DB::transaction(function () use ($course, $fullName, $email, $phone, $product, $accessEndsAt) {
            $order = Order::query()->create([
                'order_code' => 'YFD-CRS-'.Str::upper(Str::random(8)),
                'full_name' => $fullName,
                'email' => $email,
                'phone' => $phone,
                'plan' => self::PRODUCT_CODE,
                'digital_product_id' => $product->id,
                'product_name' => $product->name,
                'amount' => 0,
                'original_price' => 0,
                'discount_amount' => 0,
                'currency' => 'IDR',
                'status' => 'paid',
                'payment_gateway' => 'admin',
                'payment_reference' => 'ftsa-course:'.$course->registration_code,
                'admin_note' => 'FTSA Course '.$course->company_name.' ('.$course->registration_code.')',
                'paid_at' => now(),
            ]);

            $license = $this->licenses->resolveLicenseForPaidOrder($order);
            $license->expires_at = $accessEndsAt;
            $license->status = 'active';
            $license->save();

            $order->license_id = $license->id;
            $order->save();

            $this->portalAccess->ensureLicensePortalActivation($license);

            $registrant = FtsaCourseRegistrant::query()->create([
                'ftsa_course_id' => $course->id,
                'full_name' => $fullName,
                'email' => $email,
                'phone' => $phone,
                'registered_at' => now(),
                'access_ends_at' => $accessEndsAt,
                'order_id' => $order->id,
                'license_id' => $license->id,
            ]);

            return [
                'registrant' => $registrant,
                'course' => $course,
                'license_key' => (string) $license->license_key,
                'access_ends_at' => $accessEndsAt->timezone(config('portal.display_timezone', 'Asia/Jakarta'))->format('d M Y H:i'),
                'already_registered' => false,
            ];
        });

        return $payload;
    }
}
