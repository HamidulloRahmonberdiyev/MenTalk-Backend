<?php

declare(strict_types=1);

use App\Modules\Ai\Services\PromptStore;
use App\Modules\Language\Services\CachedLanguageCatalog;
use App\Modules\Speech\Support\GeminiVoices;
use Database\Seeders\PromptSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Learners pick the tutor's voice in the app, from a list admins keep in the panel. A conversation
 * keeps the voice it started with, and a male voice comes with a male tutor: his own name per language
 * and masculine forms when he speaks about himself, so what is said always matches who says it.
 *
 * Deploys run migrations but not seeders, so the starting list, the male tutor names and the two new
 * prompts are written here. Nothing an admin already set is overwritten.
 */
return new class extends Migration
{
    /** Three female and three male voices to start with. */
    private const VOICES = [
        ['Sulafat', ['uz' => 'Iliq', 'ru' => 'Тёплый', 'en' => 'Warm']],
        ['Achernar', ['uz' => 'Yumshoq', 'ru' => 'Мягкий', 'en' => 'Soft']],
        ['Vindemiatrix', ['uz' => 'Mayin', 'ru' => 'Нежный', 'en' => 'Gentle']],
        ['Achird', ['uz' => 'Do‘stona', 'ru' => 'Дружелюбный', 'en' => 'Friendly']],
        ['Algieba', ['uz' => 'Silliq', 'ru' => 'Плавный', 'en' => 'Smooth']],
        ['Iapetus', ['uz' => 'Aniq', 'ru' => 'Чёткий', 'en' => 'Clear']],
    ];

    private const MALE_TUTORS = ['ru' => 'Ivan', 'en' => 'James'];

    private const PROMPTS = ['tutor.self.female' => PromptSeeder::TUTOR_SELF_FEMALE, 'tutor.self.male' => PromptSeeder::TUTOR_SELF_MALE];

    public function up(): void
    {
        Schema::create('tutor_voices', function (Blueprint $t) {
            $t->id();
            // As the app shows it, per interface language: {"uz": "Iliq", "ru": "Тёплый", "en": "Warm"}.
            $t->json('name');
            // Gemini's prebuilt voice name; the gender follows from it.
            $t->string('voice', 40);
            $t->string('gender', 8);
            $t->unsignedSmallInteger('sort')->default(0);
            $t->boolean('active')->default(true);
            $t->timestamps();
        });

        foreach (self::VOICES as $sort => [$voice, $name]) {
            DB::table('tutor_voices')->insert([
                'name' => json_encode($name, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'voice' => $voice,
                'gender' => GeminiVoices::gender($voice)?->value,
                'sort' => $sort,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('conversations', function (Blueprint $t) {
            // The Gemini voice the learner picked when it started; null: the language's default voice.
            $t->string('voice', 40)->nullable()->after('target_language');
        });

        Schema::table('languages', function (Blueprint $t) {
            $t->string('male_tutor_name', 40)->nullable()->after('tutor_name');
        });

        foreach (self::MALE_TUTORS as $code => $name) {
            DB::table('languages')->where('code', $code)->whereNull('male_tutor_name')->update(['male_tutor_name' => $name]);
        }

        foreach (self::PROMPTS as $key => $body) {
            DB::table('ai_prompts')->insertOrIgnore(['key' => $key, 'body' => $body, 'active' => true, 'created_at' => now(), 'updated_at' => now()]);
        }

        Cache::forget(PromptStore::CACHE_KEY);
        Cache::forget(CachedLanguageCatalog::CACHE_KEY);
    }

    public function down(): void
    {
        DB::table('ai_prompts')->whereIn('key', array_keys(self::PROMPTS))->delete();

        Schema::table('languages', function (Blueprint $t) {
            $t->dropColumn('male_tutor_name');
        });

        Schema::table('conversations', function (Blueprint $t) {
            $t->dropColumn('voice');
        });

        Schema::dropIfExists('tutor_voices');

        Cache::forget(PromptStore::CACHE_KEY);
        Cache::forget(CachedLanguageCatalog::CACHE_KEY);
    }
};
