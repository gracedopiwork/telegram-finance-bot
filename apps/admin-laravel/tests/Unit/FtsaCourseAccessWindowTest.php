<?php

namespace Tests\Unit;

use App\Models\FtsaCourse;
use Carbon\Carbon;
use Tests\TestCase;

class FtsaCourseAccessWindowTest extends TestCase
{
    public function test_access_ends_thirty_days_after_workshop(): void
    {
        $course = new FtsaCourse([
            'workshop_at' => '2026-09-12',
            'access_days' => 30,
            'is_active' => true,
        ]);

        $ends = $course->accessEndsAt();
        $this->assertSame('2026-10-12', $ends->toDateString());
        $this->assertTrue($course->registrationWindowOpen(Carbon::parse('2026-10-01 10:00:00')));
        $this->assertFalse($course->registrationWindowOpen(Carbon::parse('2026-10-13 00:00:01')));
    }

    public function test_inactive_course_blocks_registration_window(): void
    {
        $course = new FtsaCourse([
            'workshop_at' => now()->toDateString(),
            'access_days' => 30,
            'is_active' => false,
        ]);

        $this->assertFalse($course->registrationWindowOpen());
    }
}
