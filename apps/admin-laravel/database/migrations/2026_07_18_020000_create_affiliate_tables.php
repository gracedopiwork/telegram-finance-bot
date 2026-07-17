<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('license_id')->nullable()->constrained('licenses')->nullOnDelete();
            $table->string('email', 190);
            $table->string('name', 120)->nullable();
            $table->string('referral_code', 32)->unique();
            $table->string('npwp', 32)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('email');
        });

        Schema::create('affiliate_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained('affiliates')->cascadeOnDelete();
            $table->unsignedInteger('gross_amount');
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->unsignedInteger('tax_amount')->default(0);
            $table->unsignedInteger('net_amount');
            $table->string('npwp_snapshot', 32)->nullable();
            $table->string('status', 20)->default('pending'); // pending|approved|rejected|paid
            $table->text('admin_note')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['affiliate_id', 'status']);
        });

        Schema::create('affiliate_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained('affiliates')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->unsignedInteger('amount');
            $table->string('status', 20)->default('available'); // available|claimed|cancelled
            $table->foreignId('claim_id')->nullable()->constrained('affiliate_claims')->nullOnDelete();
            $table->timestamps();

            $table->unique('order_id');
            $table->index(['affiliate_id', 'status']);
        });

        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('referral_code', 32)->nullable()->after('telegram_username');
                $table->foreignId('affiliate_id')->nullable()->after('referral_code')->constrained('affiliates')->nullOnDelete();
                $table->unsignedInteger('referral_discount')->default(0)->after('discount_amount');
            });
        }

        if (Schema::hasTable('site_settings')) {
            $rows = [
                ['key' => 'affiliate.enabled', 'value' => '1', 'type' => 'text', 'group' => 'affiliate', 'label' => 'Aktifkan referral bot (1/0)', 'sort' => 1],
                ['key' => 'affiliate.discount_amount', 'value' => '25000', 'type' => 'text', 'group' => 'affiliate', 'label' => 'Diskon pembeli (Rp)', 'sort' => 2],
                ['key' => 'affiliate.commission_amount', 'value' => '25000', 'type' => 'text', 'group' => 'affiliate', 'label' => 'Komisi referrer (Rp)', 'sort' => 3],
                ['key' => 'affiliate.eligible_product_codes', 'value' => 'yfd-bot-telegram', 'type' => 'text', 'group' => 'affiliate', 'label' => 'Kode produk eligible (pisah koma)', 'sort' => 4],
                ['key' => 'affiliate.tax_with_npwp_percent', 'value' => '0', 'type' => 'text', 'group' => 'affiliate', 'label' => 'Pajak klaim jika ada NPWP (%)', 'sort' => 5],
                ['key' => 'affiliate.tax_without_npwp_percent', 'value' => '0', 'type' => 'text', 'group' => 'affiliate', 'label' => 'Pajak klaim jika tanpa NPWP (%)', 'sort' => 6],
                ['key' => 'affiliate.min_claim_amount', 'value' => '25000', 'type' => 'text', 'group' => 'affiliate', 'label' => 'Minimal klaim (Rp)', 'sort' => 7],
            ];

            foreach ($rows as $row) {
                if (DB::table('site_settings')->where('key', $row['key'])->exists()) {
                    continue;
                }
                DB::table('site_settings')->insert(array_merge($row, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }

            Cache::forget('site_settings.all');
            Cache::forget('settings.group.affiliate');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropConstrainedForeignId('affiliate_id');
                $table->dropColumn(['referral_code', 'referral_discount']);
            });
        }

        Schema::dropIfExists('affiliate_commissions');
        Schema::dropIfExists('affiliate_claims');
        Schema::dropIfExists('affiliates');

        if (Schema::hasTable('site_settings')) {
            DB::table('site_settings')->where('group', 'affiliate')->delete();
            Cache::forget('site_settings.all');
        }
    }
};
