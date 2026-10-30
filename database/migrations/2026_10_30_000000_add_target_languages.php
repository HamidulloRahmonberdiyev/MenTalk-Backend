<?php

declare(strict_types=1);

use Database\Seeders\LanguageSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The app teaches more than Russian. Languages become data (`languages`), scenarios get one brief
 * per language, and profiles, conversations and words record their language.
 *
 * Language columns hold codes without a foreign key: languages are switched off, never deleted
 * (the admin panel does not allow it), and the codes are validated by `TaughtLanguage`.
 * Everything that existed before this migration was Russian, hence the literal 'ru' defaults.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('languages', function (Blueprint $t) {
            $t->string('code', 8)->primary();
            $t->string('name', 40);
            $t->jsonb('title');
            $t->string('tutor_name', 40);
            $t->string('voice', 40)->nullable();
            $t->string('script_note', 40)->nullable();
            $t->text('follow_ups');
            $t->text('rules')->nullable();
            $t->string('basic_words', 160);
            $t->string('dictionary_form', 160);
            $t->unsignedSmallInteger('sort')->default(0);
            $t->boolean('active')->default(true);
            $t->timestamps();
        });

        // The app cannot hold a conversation without its languages, so they ship with the schema
        // rather than waiting for `db:seed`. The seeder is idempotent and keeps admin additions.
        (new LanguageSeeder)->run();

        Schema::create('scenario_briefs', function (Blueprint $t) {
            $t->id();
            $t->string('scenario_id', 24);
            $t->foreign('scenario_id')->references('id')->on('scenarios')->cascadeOnDelete();
            $t->string('language', 8);
            $t->text('brief');
            $t->text('next_topics');
            $t->unique(['scenario_id', 'language']);
        });

        DB::table('scenarios')->orderBy('id')->get(['id', 'brief', 'next_topics'])->each(
            fn (object $scenario) => DB::table('scenario_briefs')->insert([
                'scenario_id' => $scenario->id,
                'language' => 'ru',
                'brief' => $scenario->brief,
                'next_topics' => $scenario->next_topics,
            ]),
        );

        Schema::table('scenarios', fn (Blueprint $t) => $t->dropColumn(['brief', 'next_topics']));

        Schema::table('learner_profiles', fn (Blueprint $t) => $t->string('target_language', 8)->default('ru'));
        Schema::table('conversations', fn (Blueprint $t) => $t->string('target_language', 8)->default('ru'));

        // The same spelling can be a word in two languages, so a word is unique per language.
        // The new index is created first: MySQL needs one starting with user_id for the foreign key.
        Schema::table('vocab_cards', fn (Blueprint $t) => $t->string('language', 8)->default('ru'));
        Schema::table('vocab_cards', function (Blueprint $t) {
            $t->unique(['user_id', 'language', 'word']);
            $t->dropUnique(['user_id', 'word']);
        });
    }

    public function down(): void
    {
        Schema::table('vocab_cards', function (Blueprint $t) {
            $t->unique(['user_id', 'word']);
            $t->dropUnique(['user_id', 'language', 'word']);
        });
        Schema::table('vocab_cards', fn (Blueprint $t) => $t->dropColumn('language'));
        Schema::table('conversations', fn (Blueprint $t) => $t->dropColumn('target_language'));
        Schema::table('learner_profiles', fn (Blueprint $t) => $t->dropColumn('target_language'));

        Schema::table('scenarios', function (Blueprint $t) {
            $t->text('brief')->default('');
            $t->text('next_topics')->default('');
        });

        DB::table('scenario_briefs')->where('language', 'ru')->get()->each(
            fn (object $brief) => DB::table('scenarios')->where('id', $brief->scenario_id)
                ->update(['brief' => $brief->brief, 'next_topics' => $brief->next_topics]),
        );

        Schema::dropIfExists('scenario_briefs');
        Schema::dropIfExists('languages');
    }
};
