<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('category_bucket_mappings')) {
            return;
        }

        Schema::create('category_bucket_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('category', 128);
            $table->string('sub_category', 128)->nullable();
            $table->string('bucket', 64);
            $table->string('transaction_type', 16)->default('expense');
            $table->string('nature', 32)->nullable();
            $table->text('match_keywords')->nullable();
            $table->text('reason')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_bucket_mappings');
    }
};
