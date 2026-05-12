<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('spreadsheet_id', 128)->nullable()->after('license_id');
            $table->string('spreadsheet_url', 512)->nullable()->after('spreadsheet_id');
            $table->timestamp('purchase_delivery_sent_at')->nullable()->after('spreadsheet_url');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn([
                'spreadsheet_id',
                'spreadsheet_url',
                'purchase_delivery_sent_at',
            ]);
        });
    }
};
