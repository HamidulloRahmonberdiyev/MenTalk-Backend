<?php

declare(strict_types=1);

use App\Modules\Ai\Services\PromptStore;
use Database\Seeders\PromptSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Translator answers, shared by all learners (`Translation`). Rows are found by `key`, a hash of
 * everything that changes the answer (`TranslationQuery::key()`): the text alone can be up to 500
 * characters, too long for a unique index on MySQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translations', function (Blueprint $t) {
            $t->id();
            $t->char('key', 64)->unique();
            // As requested: `source_language` may be 'auto'; the detected one is in `result`.
            $t->string('source_language', 8);
            $t->string('target_language', 8);
            $t->string('ui_language', 2);
            $t->string('uz_script', 8);
            $t->text('text');
            $t->json('result');
            // Times it was served from here instead of the AI; never-reused rows get pruned.
            $t->unsignedInteger('hits')->default(0);
            $t->timestampTz('last_hit_at')->nullable();
            $t->timestampsTz();

            $t->index(['hits', 'created_at']);
        });

        // Deploys run migrations but not seeders. Never overwrites a prompt an admin already edited.
        DB::table('ai_prompts')->insertOrIgnore([
            'key' => 'translator',
            'body' => PromptSeeder::TRANSLATOR,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Cache::forget(PromptStore::CACHE_KEY);
    }

    public function down(): void
    {
        Schema::dropIfExists('translations');
        DB::table('ai_prompts')->where('key', 'translator')->delete();
        Cache::forget(PromptStore::CACHE_KEY);
    }
};
