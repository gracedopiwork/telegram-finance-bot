<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('category_bucket_mappings')) {
            return;
        }

        $rows = [
            [
                'category' => '*',
                'sub_category' => '-',
                'bucket' => 'Essential Living',
                'transaction_type' => 'expense',
                'nature' => null,
                'match_keywords' => 'les olahraga,coaching olahraga,kursus olahraga,personal trainer,fitness coach,kelas gym,kelas yoga,kelas pilates,les tenis,coaching tenis,pelatih tenis,kelas tenis,les renang,coaching renang,pelatih renang,kelas renang,les badminton,les bulu tangkis,coaching badminton,coaching basket,coaching sepak bola,coaching futsal,coaching golf',
                'reason' => 'Les/coaching olahraga mendukung kesehatan',
                'sort_order' => 20,
            ],
            [
                'category' => '*',
                'sub_category' => '-',
                'bucket' => 'Future Building',
                'transaction_type' => 'expense',
                'nature' => null,
                'match_keywords' => 'seminar,simposium,symposium,workshop,sertifikasi,pelatihan,kursus,course,conference,penelitian,buku pengembangan diri,pengembangan diri,self development,psychology of money,psychologi of money,les,coaching,mentoring,kelas piano,les piano,kursus piano,les musik,kelas musik,les vokal,les bahasa,kursus bahasa,public speaking',
                'reason' => 'Semua self-development selain olahraga',
                'sort_order' => 22,
            ],
            [
                'category' => 'Pendidikan',
                'sub_category' => '-',
                'bucket' => 'Essential Living',
                'transaction_type' => 'expense',
                'nature' => 'Need',
                'match_keywords' => 'spp,uang sekolah,ukt,buku wajib,kebutuhan sekolah anak,les wajib,bimbel sekolah,kuliah',
                'reason' => 'Pendidikan wajib',
                'sort_order' => 33,
            ],
        ];

        foreach ($rows as $row) {
            DB::table('category_bucket_mappings')->updateOrInsert(
                [
                    'category' => $row['category'],
                    'sub_category' => $row['sub_category'],
                    'transaction_type' => $row['transaction_type'],
                    'sort_order' => $row['sort_order'],
                ],
                [
                    'bucket' => $row['bucket'],
                    'nature' => $row['nature'],
                    'match_keywords' => $row['match_keywords'],
                    'reason' => $row['reason'],
                    'is_active' => true,
                    'updated_at' => now(),
                ],
            );
        }

        Cache::forget('category_bucket_mappings:active');
    }

    public function down(): void
    {
        // Tidak menimpa penyesuaian mapping yang mungkin sudah dibuat admin.
    }
};
