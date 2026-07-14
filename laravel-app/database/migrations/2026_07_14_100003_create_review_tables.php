<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_listing_id')->constrained()->cascadeOnDelete();
            $table->string('external_review_id');
            $table->string('author_name')->nullable();
            $table->unsignedTinyInteger('rating'); // 1-5
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->string('language', 10)->nullable();
            $table->string('app_version')->nullable();
            $table->string('device')->nullable();
            $table->string('country', 2)->nullable();
            $table->timestamp('review_created_at');
            $table->timestamp('review_updated_at')->nullable();
            $table->text('reply_body')->nullable();
            $table->timestamp('reply_at')->nullable();
            $table->decimal('sentiment_score', 6, 4)->nullable(); // -1..1
            $table->string('sentiment_label')->nullable(); // positive | neutral | negative
            $table->decimal('sentiment_magnitude', 6, 4)->nullable();
            $table->string('analyzer')->nullable(); // lexicon | google_nl | ...
            $table->timestamp('analyzed_at')->nullable(); // null => needs analysis
            $table->json('raw')->nullable();
            $table->timestamps();

            $table->unique(['store_listing_id', 'external_review_id']);
            $table->index(['store_listing_id', 'review_created_at']);
            $table->index(['rating', 'review_created_at']);
        });

        Schema::create('review_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('store_listing_id')->constrained()->cascadeOnDelete();
            $table->string('reason'); // low_rating | negative_sentiment | both
            $table->string('severity')->default('medium'); // low | medium | high
            $table->string('status')->default('open'); // open | acknowledged | resolved
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('notified_at')->nullable();
            $table->json('notified_channels')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_alerts');
        Schema::dropIfExists('reviews');
    }
};
