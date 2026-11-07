<?php

declare(strict_types=1);

use App\Modules\Ai\Services\PromptStore;
use App\Modules\Pronunciation\Models\Phrase;
use App\Modules\Pronunciation\Support\StarterPhrases;
use Database\Seeders\PromptSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The shared pool of read-aloud lines (`Phrase`) and which learner was given which. The pool is
 * filled with the starter lines here: deploys run migrations, not seeders.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pronunciation_phrases', function (Blueprint $t) {
            $t->id();
            $t->string('language', 8);
            $t->string('level', 8);
            $t->string('text', 255);
            // `Phrase::fingerprint()`: the same line in another case or punctuation is not added twice.
            $t->char('fingerprint', 64);
            // 'starter' (hand-picked), 'ai', or 'manual' (added by an admin).
            $t->string('source', 8);
            // Switched off, a line stays in learners' history but is no longer handed out.
            $t->boolean('active')->default(true);
            $t->timestampsTz();

            $t->unique(['language', 'fingerprint']);
            $t->index(['language', 'level', 'active']);
        });

        Schema::create('pronunciation_phrase_views', function (Blueprint $t) {
            $t->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('phrase_id')->constrained('pronunciation_phrases')->cascadeOnDelete();
            $t->timestampTz('seen_at');

            $t->primary(['user_id', 'phrase_id']);
            // "Seen longest ago" for one learner.
            $t->index(['user_id', 'seen_at']);
        });

        $now = now();
        $rows = [];
        foreach (StarterPhrases::LINES as $language => $levels) {
            foreach ($levels as $level => $lines) {
                foreach ($lines as $line) {
                    $rows[] = [
                        'language' => $language,
                        'level' => $level,
                        'text' => $line,
                        'fingerprint' => Phrase::fingerprint($line),
                        'source' => 'starter',
                        'active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }
        DB::table('pronunciation_phrases')->insertOrIgnore($rows);

        // Never overwrites a prompt an admin already edited.
        DB::table('ai_prompts')->insertOrIgnore([
            'key' => 'pronunciation.phrases',
            'body' => PromptSeeder::PRONUNCIATION_PHRASES,
            'active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        Cache::forget(PromptStore::CACHE_KEY);
    }

    public function down(): void
    {
        Schema::dropIfExists('pronunciation_phrase_views');
        Schema::dropIfExists('pronunciation_phrases');
        DB::table('ai_prompts')->where('key', 'pronunciation.phrases')->delete();
        Cache::forget(PromptStore::CACHE_KEY);
    }
};
