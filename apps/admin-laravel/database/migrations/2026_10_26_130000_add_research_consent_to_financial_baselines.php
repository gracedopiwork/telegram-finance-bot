<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pilihan persetujuan penelitian dari halaman pengantar FHCU (/check-up).
 *
 * research_consent: true = "Saya bersedia", false = "Saya tidak bersedia",
 * null = belum pernah ditanya (baris lama, atau user portal yang langsung ke diagnostik).
 * research_consent_at + research_consent_version: kapan dan teks versi mana yang dipilih.
 *
 * Hanya skema, tanpa backfill — baris lama tetap null. Rollback menghapus pilihan yang
 * sudah terkumpul: dump kolom-kolom ini dulu sebelum rollback di produksi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_baselines', function (Blueprint $table): void {
            if (! Schema::hasColumn('financial_baselines', 'research_consent')) {
                $table->boolean('research_consent')->nullable()->after('answers_json');
            }
            if (! Schema::hasColumn('financial_baselines', 'research_consent_at')) {
                $table->timestamp('research_consent_at')->nullable()->after('research_consent');
            }
            if (! Schema::hasColumn('financial_baselines', 'research_consent_version')) {
                $table->string('research_consent_version', 16)->nullable()->after('research_consent_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('financial_baselines', function (Blueprint $table): void {
            foreach (['research_consent_version', 'research_consent_at', 'research_consent'] as $column) {
                if (Schema::hasColumn('financial_baselines', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
