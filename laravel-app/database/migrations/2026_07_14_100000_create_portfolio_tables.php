<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apps', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('store_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_id')->constrained()->cascadeOnDelete();
            $table->string('platform'); // android | ios
            $table->string('store_app_id'); // package name | Apple numeric id
            $table->string('bundle_id')->nullable();
            $table->string('developer_account')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['platform', 'store_app_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_listings');
        Schema::dropIfExists('apps');
    }
};
