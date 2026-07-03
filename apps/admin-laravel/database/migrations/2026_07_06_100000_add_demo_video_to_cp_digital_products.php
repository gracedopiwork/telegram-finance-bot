<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('cp_digital_products', function (Blueprint $table) {
            $table->boolean('demo_video_enabled')->default(false)->after('meta_description');
            $table->string('demo_video_url', 500)->nullable()->after('demo_video_enabled');
            $table->text('demo_video_description')->nullable()->after('demo_video_url');
        });
    }

    public function down(): void
    {
        Schema::table('cp_digital_products', function (Blueprint $table) {
            $table->dropColumn(['demo_video_enabled', 'demo_video_url', 'demo_video_description']);
        });
    }
};
