<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_business_connections', function (Blueprint $table) {
            $table->id();
            $table->string('account_name')->nullable(); // accounts/123
            $table->string('account_label')->nullable();
            $table->string('location_name')->nullable(); // locations/456
            $table->string('location_title')->nullable();
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->decimal('average_rating', 3, 1)->nullable();
            $table->unsignedInteger('total_review_count')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });

        Schema::create('google_business_reviews', function (Blueprint $table) {
            $table->id();
            $table->string('google_review_id')->unique();
            $table->string('reviewer_name')->nullable();
            $table->string('reviewer_photo_url', 512)->nullable();
            $table->unsignedTinyInteger('rating')->default(5);
            $table->text('comment')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('reply_comment')->nullable();
            $table->timestamp('reply_updated_at')->nullable();
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->index(['is_published', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_business_reviews');
        Schema::dropIfExists('google_business_connections');
    }
};
