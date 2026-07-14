<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('source'); // webhook | crm | google_ads | meta_ads | ...
            $table->string('external_id');
            $table->foreignId('app_id')->nullable()->constrained()->nullOnDelete();
            $table->string('campaign')->nullable();
            $table->string('medium')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('status')->default('new'); // new | contacted | qualified | converted | lost
            $table->decimal('value', 12, 2)->nullable();
            $table->timestamp('received_at');
            $table->json('raw')->nullable();
            $table->timestamps();

            $table->unique(['source', 'external_id']);
            $table->index('received_at');
            $table->index(['status', 'received_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
