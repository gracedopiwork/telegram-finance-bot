<?php

namespace Tests\Unit;

use App\Support\KeywordMatch;
use PHPUnit\Framework\TestCase;

class KeywordMatchTest extends TestCase
{
    public function test_short_token_does_not_match_inside_longer_word(): void
    {
        $this->assertFalse(KeywordMatch::contains('sales meeting', 'les'));
        $this->assertFalse(KeywordMatch::contains('pendidikan anak', 'idi'));
        $this->assertTrue(KeywordMatch::contains('bayar les piano', 'les'));
        $this->assertTrue(KeywordMatch::contains('iuran idi', 'idi'));
        $this->assertTrue(KeywordMatch::contains('bayar pph21', 'pph'));
    }

    public function test_longer_tokens_stay_substring(): void
    {
        $this->assertTrue(KeywordMatch::contains('beli kopi starbucks', 'kopi'));
        $this->assertTrue(KeywordMatch::contains('ngopi meeting', 'ngopi'));
        $this->assertTrue(KeywordMatch::contains('tumbler ganti yang rusak', 'tumbler'));
        $this->assertTrue(KeywordMatch::contains('premi asuransi jiwa', 'premi asuransi'));
        $this->assertFalse(KeywordMatch::contains('cushion premium', 'premi asuransi'));
    }
}
