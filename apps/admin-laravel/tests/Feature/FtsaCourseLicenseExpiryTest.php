<?php

namespace Tests\Feature;

use App\Models\CpDigitalProduct;
use App\Models\FtsaCourse;
use App\Models\License;
use App\Models\Order;
use App\Services\FtsaCourseService;
use App\Services\LicenseEntitlementService;
use App\Services\PortalPasswordService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Satu lisensi per email: pendaftaran FTSA Course memakai ulang lisensi First Aid yang sudah ada.
 * Course tidak boleh memotong masa First Aid — dulu expires_at ditimpa jendela course, sehingga
 * portal ("Tidak ada lisensi aktif") dan bot ("Lisensi sudah expired") menolak setelah course selesai.
 */
class FtsaCourseLicenseExpiryTest extends TestCase
{
    use RefreshDatabase;

    private const EMAIL = 'founder@example.com';

    protected function setUp(): void
    {
        parent::setUp();
        // Default bawaan config/portal.php — .env lokal bisa menimpanya tanpa kode course.
        config(['portal.ftsa.unlock_product_codes' => ['yfd-ftsa-premium', 'yfd-ftsa-workshop', 'yfd-first-aid-ftsa', 'yfd-ftsa-course']]);
    }

    public function test_course_registration_keeps_a_twelve_month_first_aid_license(): void
    {
        $firstAidEnds = now()->addMonths(12)->startOfSecond();
        $license = $this->firstAidBuyer($firstAidEnds);
        $course = $this->courseStartingToday();

        app(FtsaCourseService::class)->register('WS-TEST', 'Founder', self::EMAIL);

        $this->assertTrue($license->fresh()->expires_at->eq($firstAidEnds));

        $this->travelTo($course->accessEndsAt()->addDay());
        $this->assertSame($license->id, app(PortalPasswordService::class)->resolveLicenseForEmail(self::EMAIL)?->id);
        $this->assertFalse(
            app(LicenseEntitlementService::class)->hasActiveFtsaEntitlementForEmail(self::EMAIL),
            'Course FTSA access must still close at the course window, not at the First Aid expiry.'
        );
    }

    public function test_course_registration_keeps_a_lifetime_license_lifetime(): void
    {
        $license = $this->firstAidBuyer(null);
        $this->courseStartingToday();

        app(FtsaCourseService::class)->register('WS-TEST', 'Founder', self::EMAIL);

        $this->assertNull($license->fresh()->expires_at);
    }

    public function test_course_only_registrant_gets_a_license_ending_with_the_course(): void
    {
        $course = $this->courseStartingToday();

        $result = app(FtsaCourseService::class)->register('WS-TEST', 'Peserta', 'peserta@example.com');

        $license = License::query()->where('license_key', $result['license_key'])->firstOrFail();
        $this->assertTrue($license->expires_at->eq($course->accessEndsAt()->startOfSecond()));
        $this->assertTrue(app(LicenseEntitlementService::class)->hasActiveFtsaEntitlementForEmail('peserta@example.com'));

        $this->travelTo($course->accessEndsAt()->addDay());
        $this->assertNull(app(PortalPasswordService::class)->resolveLicenseForEmail('peserta@example.com'));
        $this->assertFalse(app(LicenseEntitlementService::class)->hasActiveFtsaEntitlementForEmail('peserta@example.com'));
    }

    private function firstAidBuyer(?Carbon $expiresAt): License
    {
        $product = CpDigitalProduct::query()->firstOrCreate(['code' => 'yfd-first-aid'], ['name' => 'YFD First Aid']);

        $license = License::query()->create([
            'license_key' => 'TFB-AAAA-BBBB-CCCC',
            'plan' => 'yfd-first-aid',
            'status' => 'active',
            'expires_at' => $expiresAt,
            'max_accounts' => 1,
            'assigned_user_id' => 111222333,
        ]);

        Order::query()->create([
            'order_code' => 'YFD-FA-TEST01',
            'full_name' => 'Founder',
            'email' => self::EMAIL,
            'plan' => 'yfd-first-aid',
            'digital_product_id' => $product->id,
            'product_name' => 'YFD First Aid',
            'amount' => 299000,
            'currency' => 'IDR',
            'status' => 'paid',
            'payment_gateway' => 'pivot',
            'paid_at' => now(),
            'license_id' => $license->id,
        ]);

        return $license;
    }

    private function courseStartingToday(): FtsaCourse
    {
        CpDigitalProduct::query()->firstOrCreate(['code' => FtsaCourseService::PRODUCT_CODE], ['name' => 'FTSA Course']);

        return FtsaCourse::query()->create([
            'name' => 'Workshop',
            'company_name' => 'PT Contoh',
            'registration_code' => 'WS-TEST',
            'workshop_at' => now()->toDateString(),
            'access_days' => 30,
            'is_active' => true,
        ]);
    }
}
