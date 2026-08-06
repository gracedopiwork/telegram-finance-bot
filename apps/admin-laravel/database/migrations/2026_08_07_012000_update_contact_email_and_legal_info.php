<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Update contact email + Legal Information copy on Informasi page.
 */
return new class extends Migration
{
    private const NEW_EMAIL = 'admin.findoc@yourfinancialdoctor.id';

    private const OLD_EMAIL = 'yfinancialdoctor@gmail.com';

    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $now = now();

        DB::table('settings')->where('key', 'contact.email')->update([
            'value' => self::NEW_EMAIL,
            'updated_at' => $now,
        ]);

        $legalRows = [
            [
                'key' => 'page.informasi.legal_title',
                'value' => 'Legal Information',
                'type' => 'text',
                'group' => 'page_informasi',
                'label' => 'Informasi — Legal judul',
                'sort' => 15,
            ],
            [
                'key' => 'page.informasi.legal_body',
                'value' => 'Your Financial Doctor merupakan brand yang dimiliki dan dioperasikan oleh Ayuti Bulaan. Nomor Induk Berusaha (NIB): 1205260000123',
                'type' => 'textarea',
                'group' => 'page_informasi',
                'label' => 'Informasi — Legal isi',
                'sort' => 16,
            ],
        ];

        foreach ($legalRows as $row) {
            $exists = DB::table('settings')->where('key', $row['key'])->exists();
            if ($exists) {
                continue;
            }
            DB::table('settings')->insert(array_merge($row, [
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }

        if (Schema::hasTable('cp_faqs')) {
            $faqs = DB::table('cp_faqs')
                ->where('answer', 'like', '%'.self::OLD_EMAIL.'%')
                ->get();
            foreach ($faqs as $faq) {
                DB::table('cp_faqs')->where('id', $faq->id)->update([
                    'answer' => str_replace(self::OLD_EMAIL, self::NEW_EMAIL, (string) $faq->answer),
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        DB::table('settings')->where('key', 'contact.email')->update([
            'value' => self::OLD_EMAIL,
            'updated_at' => now(),
        ]);

        DB::table('settings')->whereIn('key', [
            'page.informasi.legal_title',
            'page.informasi.legal_body',
        ])->delete();
    }
};
