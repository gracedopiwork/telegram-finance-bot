<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cp_services')) {
            return;
        }

        $rows = DB::table('cp_services')->select(['id', 'features'])->get();
        foreach ($rows as $row) {
            $features = json_decode((string) $row->features, true);
            if (! is_array($features)) {
                continue;
            }

            $changed = false;
            $footnote = (string) ($features['footnote'] ?? '');
            if ($footnote !== '') {
                $updated = str_replace(
                    [
                        'Start Rp 100k (30 menit).',
                        'Start Rp 100k (30 menit)',
                        'Rp 150k (30 menit–1 jam).',
                        'Rp 150k (30 menit–1 jam)',
                        '(30 menit)',
                    ],
                    [
                        'Start Rp 100k (minimal 60 menit).',
                        'Start Rp 100k (minimal 60 menit)',
                        'Rp 150k (minimal 60 menit).',
                        'Rp 150k (minimal 60 menit)',
                        '(minimal 60 menit)',
                    ],
                    $footnote,
                );
                if ($updated !== $footnote) {
                    $features['footnote'] = $updated;
                    $changed = true;
                }
            }

            $items = $features['items'] ?? null;
            if (is_array($items)) {
                foreach ($items as $i => $item) {
                    if (! is_string($item)) {
                        continue;
                    }
                    $updatedItem = str_replace(
                        ['(30 menit)', '30 menit'],
                        ['(minimal 60 menit)', 'minimal 60 menit'],
                        $item,
                    );
                    if ($updatedItem !== $item) {
                        $features['items'][$i] = $updatedItem;
                        $changed = true;
                    }
                }
            }

            if ($changed) {
                DB::table('cp_services')->where('id', $row->id)->update([
                    'features' => json_encode($features, JSON_UNESCAPED_UNICODE),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // no-op: duration copy corrected forward-only
    }
};
