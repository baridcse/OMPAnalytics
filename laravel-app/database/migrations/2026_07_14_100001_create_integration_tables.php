<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integrations', function (Blueprint $table) {
            $table->id();
            $table->string('provider'); // google_play | app_store | admob | ga4 | leads_webhook | leads_crm | fake
            $table->string('name');
            $table->boolean('is_enabled')->default(true);
            $table->text('credentials')->nullable(); // encrypted:array cast
            $table->json('config')->nullable();
            $table->foreignId('store_listing_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('last_synced_at')->nullable();
            $table->string('last_sync_status')->nullable(); // success | failed | running
            $table->text('last_error')->nullable();
            $table->json('sync_cursor')->nullable();
            $table->timestamps();
        });

        Schema::create('sync_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('integration_id')->constrained()->cascadeOnDelete();
            $table->string('provider');
            $table->string('capability'); // metrics | reviews | ad_revenue | analytics | leads
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->string('status'); // running | success | failed
            $table->unsignedInteger('records_processed')->default(0);
            $table->text('error')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['integration_id', 'capability', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_runs');
        Schema::dropIfExists('integrations');
    }
};
