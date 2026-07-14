<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_performance_daily', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_listing_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('active_users')->default(0);
            $table->unsignedInteger('installs')->default(0);
            $table->unsignedInteger('uninstalls')->default(0);
            $table->decimal('crash_rate', 8, 5)->nullable(); // fraction of sessions, e.g. 0.00421
            $table->decimal('anr_rate', 8, 5)->nullable();
            $table->decimal('rating_avg', 3, 2)->nullable();
            $table->unsignedInteger('rating_count')->default(0);
            $table->string('source')->nullable();
            $table->timestamps();

            $table->unique(['store_listing_id', 'date']);
            $table->index('date');
        });

        Schema::create('ad_revenue_daily', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_listing_id')->constrained()->cascadeOnDelete();
            $table->string('network')->default('admob');
            $table->date('date');
            $table->unsignedBigInteger('impressions')->default(0);
            $table->unsignedInteger('clicks')->default(0);
            $table->decimal('ctr', 8, 5)->nullable();
            $table->decimal('ecpm', 10, 4)->nullable();
            $table->decimal('estimated_revenue', 12, 4)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->timestamps();

            $table->unique(['store_listing_id', 'network', 'date']);
            $table->index('date');
        });

        Schema::create('analytics_daily', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_listing_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('sessions')->default(0);
            $table->unsignedInteger('total_users')->default(0);
            $table->unsignedInteger('new_users')->default(0);
            $table->unsignedInteger('engaged_sessions')->default(0);
            $table->decimal('avg_engagement_time', 10, 2)->nullable(); // seconds
            $table->unsignedInteger('conversions')->default(0);
            $table->timestamps();

            $table->unique(['store_listing_id', 'date']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_daily');
        Schema::dropIfExists('ad_revenue_daily');
        Schema::dropIfExists('app_performance_daily');
    }
};
