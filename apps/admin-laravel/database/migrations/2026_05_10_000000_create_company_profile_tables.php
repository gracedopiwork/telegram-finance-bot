<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ===== Site-wide key-value settings =====
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 120)->unique();
            $table->longText('value')->nullable();
            $table->string('type', 20)->default('text'); // text|textarea|image|url|number|html
            $table->string('group', 60)->default('general');
            $table->string('label', 200)->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        // ===== Packages (paket health check up) =====
        Schema::create('cp_packages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 200);
            $table->string('name_en', 200)->nullable();
            $table->string('tier_label', 60)->nullable();
            $table->unsignedBigInteger('price')->default(0);
            $table->string('period', 30)->default('/sesi');
            $table->text('description')->nullable();
            $table->json('features')->nullable();
            $table->string('variant', 20)->default('plain');
            $table->boolean('is_recommended')->default(false);
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ===== Advisors (penasihat) =====
        Schema::create('cp_advisors', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
            $table->string('role_label', 100)->nullable();
            $table->json('badges')->nullable();
            $table->string('years_exp', 30)->nullable();
            $table->string('spec_short', 200)->nullable();
            $table->string('spec_icon', 60)->nullable();
            $table->text('spec_long')->nullable();
            $table->string('tag', 30)->nullable();
            $table->string('photo_path', 500)->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ===== Services (layanan wellness) =====
        Schema::create('cp_services', function (Blueprint $table) {
            $table->id();
            $table->string('section', 60)->default('default');
            $table->string('eyebrow', 100)->nullable();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('icon', 60)->nullable();
            $table->string('image_path', 500)->nullable();
            $table->json('features')->nullable();
            $table->string('cta_label', 100)->nullable();
            $table->string('cta_route', 100)->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ===== Locations (lokasi klinik) =====
        Schema::create('cp_locations', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->string('badge', 30)->nullable();
            $table->text('address')->nullable();
            $table->string('hours', 200)->nullable();
            $table->string('image_path', 500)->nullable();
            $table->string('maps_url', 500)->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ===== FAQs =====
        Schema::create('cp_faqs', function (Blueprint $table) {
            $table->id();
            $table->string('category', 60)->nullable();
            $table->string('question', 500);
            $table->text('answer');
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ===== Articles (Wealthpedia) =====
        Schema::create('cp_articles', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 200)->unique();
            $table->string('title', 300);
            $table->string('category', 60)->nullable();
            $table->string('read_time', 30)->nullable();
            $table->string('views_label', 30)->nullable();
            $table->text('description')->nullable();
            $table->longText('content_html')->nullable();
            $table->string('image_path', 500)->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cp_articles');
        Schema::dropIfExists('cp_faqs');
        Schema::dropIfExists('cp_locations');
        Schema::dropIfExists('cp_services');
        Schema::dropIfExists('cp_advisors');
        Schema::dropIfExists('cp_packages');
        Schema::dropIfExists('site_settings');
    }
};
