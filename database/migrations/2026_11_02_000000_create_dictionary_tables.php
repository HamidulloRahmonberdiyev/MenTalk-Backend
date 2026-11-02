<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A single global dictionary: every word of every language lives in `words`, shared by all
 * learners. What a learner knows lives in `user_words` (next migration).
 *
 * `words.language` holds a code without a foreign key, like every other language column: a word
 * can be in a language that is not taught (Uzbek translations), and `languages` lists taught ones.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('languages', fn (Blueprint $t) => $t->boolean('is_rtl')->default(false)->after('name'));

        Schema::create('words', function (Blueprint $t) {
            $t->id();
            $t->string('language', 8);
            $t->string('text', 160);
            $t->string('normalized', 160);
            // Not nullable: NULLs are distinct in a unique index, so a NULL pos would let duplicates in.
            // An empty string means "unknown".
            $t->string('pos', 16)->default('');
            $t->string('transcription', 128)->nullable();
            $t->unsignedInteger('frequency_rank')->nullable();
            // Language-specific grammar, keys per language in `Word::DETAILS` (ar: root, plural, gender;
            // ru: stress, aspect). A column, not a table per language: nothing queries these fields yet.
            $t->jsonb('details')->nullable();
            $t->foreignUlid('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestampsTz();

            // Also serves lookups by (language, normalized): no separate index needed.
            $t->unique(['language', 'normalized', 'pos']);
        });

        // A directed edge; the action that links two words writes both directions.
        Schema::create('word_translations', function (Blueprint $t) {
            $t->foreignId('word_id')->constrained('words')->cascadeOnDelete();
            $t->foreignId('translated_word_id')->constrained('words')->cascadeOnDelete();
            $t->string('sense_note', 160)->nullable();
            $t->primary(['word_id', 'translated_word_id']);
        });

        Schema::create('word_examples', function (Blueprint $t) {
            $t->id();
            $t->foreignId('word_id')->constrained('words')->cascadeOnDelete();
            $t->text('text');
            $t->text('translation')->nullable();
            $t->string('source', 8);
            $t->timestampsTz();
        });
    }

    public function down(): void
    {
        foreach (['word_examples', 'word_translations', 'words'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::table('languages', fn (Blueprint $t) => $t->dropColumn('is_rtl'));
    }
};
