<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * App Store Connect reports territories as 3-letter codes (e.g. USA),
     * while Google Play uses 2-letter codes — widen to fit both.
     */
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->string('country', 3)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->string('country', 2)->nullable()->change();
        });
    }
};
