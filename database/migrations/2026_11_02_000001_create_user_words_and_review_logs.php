<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Personal progress on the global dictionary, and the review history behind it.
 *
 * `user_words` keeps the offline sync contract of `vocab_cards`: a `synced_at` cursor and soft
 * deletes as tombstones, so a word removed on one device disappears on the others.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_words', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            // Restrict: a shared word that learners study must be merged into another, not deleted.
            $t->foreignId('word_id')->constrained('words')->restrictOnDelete();
            // The meaning this learner saved; a word has many, and another learner's must not replace it.
            $t->foreignId('translation_word_id')->nullable()->constrained('words')->nullOnDelete();
            $t->string('status', 8)->default('new');
            $t->decimal('ease', 4, 2)->default(2.5);
            $t->unsignedSmallInteger('interval_days')->default(0);
            $t->unsignedSmallInteger('repetitions')->default(0);
            $t->unsignedSmallInteger('lapses')->default(0);
            $t->timestampTz('due_at')->useCurrent();
            $t->timestampTz('last_reviewed_at')->nullable();
            $t->string('source', 8);
            $t->text('context_sentence')->nullable();
            // The inflected form as it appears in `context_sentence`, when it differs from the word.
            $t->string('form', 160)->nullable();
            $t->timestampsTz();
            $t->timestampTz('synced_at')->useCurrent();
            $t->softDeletesTz();

            $t->unique(['user_id', 'word_id']);
            $t->index(['user_id', 'due_at']);
            $t->index(['user_id', 'synced_at']);
        });

        // Append-only. Both keys are nullOnDelete so the rows outlive what they point to: cascade
        // would erase the history, restrict would block the account purge. After a purge a log is
        // anonymous yet still counts towards word difficulty and timing statistics.
        Schema::create('review_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignUlid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('word_id')->nullable()->constrained('words')->nullOnDelete();
            $t->unsignedTinyInteger('rating');
            $t->timestampTz('reviewed_at', 3);
            $t->unsignedInteger('duration_ms')->nullable();

            // Offline clients resend reviews; the same user, word and instant is the same review.
            $t->unique(['user_id', 'word_id', 'reviewed_at']);
            $t->index('word_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_logs');
        Schema::dropIfExists('user_words');
    }
};
