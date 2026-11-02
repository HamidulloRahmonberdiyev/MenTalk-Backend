<?php

declare(strict_types=1);

use App\Services\Vocabulary\WordNormalizer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Moves every learner's cards onto the global dictionary and drops `vocab_cards`.
 *
 * Card ids are kept as `user_words` ids. A card's translation was written in the learner's
 * interface language, so it becomes a word of that language. The seven-step ladder maps onto SM-2
 * through its own intervals: stage n was due INTERVAL_DAYS[n] days after its last review.
 *
 * Query builder only: models change over time, a migration must keep doing what it did.
 */
return new class extends Migration
{
    private const INTERVAL_DAYS = [0, 0, 1, 3, 7, 16, 35];

    private const KNOWN_AFTER_DAYS = 21;

    public function up(): void
    {
        $normalizer = new WordNormalizer;
        $now = now();

        $word = function (string $language, string $text) use ($normalizer, $now): int {
            $text = trim((string) preg_replace('/\s+/u', ' ', $text));
            $key = ['language' => $language, 'normalized' => $normalizer->normalize($text, $language)];

            DB::table('words')->insertOrIgnore($key + ['pos' => '', 'text' => $text, 'created_at' => $now, 'updated_at' => $now]);

            return (int) DB::table('words')->where($key)->orderBy('pos')->value('id');
        };

        DB::table('vocab_cards')
            ->leftJoin('learner_profiles', 'learner_profiles.user_id', '=', 'vocab_cards.user_id')
            ->select('vocab_cards.*', 'learner_profiles.ui_language')
            ->orderBy('vocab_cards.id')
            ->chunk(500, function ($cards) use ($word, $now) {
                foreach ($cards as $card) {
                    $wordId = $word($card->language, $card->word);
                    $translationId = $word($card->ui_language ?? 'uz', $card->translation);

                    if ($translationId !== $wordId) {
                        DB::table('word_translations')->insertOrIgnore([
                            ['word_id' => $wordId, 'translated_word_id' => $translationId],
                            ['word_id' => $translationId, 'translated_word_id' => $wordId],
                        ]);
                    }

                    $stage = min((int) $card->stage, count(self::INTERVAL_DAYS) - 1);
                    $interval = self::INTERVAL_DAYS[$stage];

                    DB::table('user_words')->insertOrIgnore([
                        'id' => $card->id,
                        'user_id' => $card->user_id,
                        'word_id' => $wordId,
                        'translation_word_id' => $translationId,
                        'status' => match (true) {
                            $stage === 0 => 'new',
                            $interval >= self::KNOWN_AFTER_DAYS => 'known',
                            default => 'learning',
                        },
                        'ease' => 2.5,
                        'interval_days' => $interval,
                        'repetitions' => $stage,
                        'lapses' => $card->lapses,
                        'due_at' => $card->due_at,
                        'last_reviewed_at' => $stage > 0 ? $card->updated_at : null,
                        'source' => 'manual',
                        'context_sentence' => $card->example,
                        'form' => $card->form,
                        'created_at' => $card->updated_at,
                        'updated_at' => $card->updated_at,
                        'synced_at' => $now,
                        'deleted_at' => $card->deleted_at,
                    ]);
                }
            });

        Schema::drop('vocab_cards');
    }

    public function down(): void
    {
        Schema::create('vocab_cards', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $t->string('language', 8)->default('ru');
            $t->string('word', 64);
            $t->string('translation', 160);
            $t->text('example')->nullable();
            $t->string('form', 64)->nullable();
            $t->unsignedTinyInteger('stage')->default(0);
            $t->unsignedSmallInteger('streak')->default(0);
            $t->unsignedSmallInteger('lapses')->default(0);
            $t->timestampTz('due_at');
            $t->timestampTz('updated_at');
            $t->timestampTz('synced_at');
            $t->softDeletes();
            $t->unique(['user_id', 'language', 'word']);
            $t->index(['user_id', 'synced_at']);
            $t->index(['user_id', 'due_at']);
        });

        DB::table('user_words')
            ->join('words', 'words.id', '=', 'user_words.word_id')
            ->leftJoin('words as translations', 'translations.id', '=', 'user_words.translation_word_id')
            ->select('user_words.*', 'words.language', 'words.text', 'translations.text as translation')
            ->orderBy('user_words.id')
            ->chunk(500, function ($rows) {
                foreach ($rows as $row) {
                    $stage = $row->status === 'new' ? 0 : max(1, count(array_filter(self::INTERVAL_DAYS, fn (int $days) => $days <= $row->interval_days)) - 1);

                    DB::table('vocab_cards')->insertOrIgnore([
                        'id' => $row->id,
                        'user_id' => $row->user_id,
                        'language' => $row->language,
                        'word' => mb_substr($row->text, 0, 64),
                        'translation' => $row->translation ?? $row->text,
                        'example' => $row->context_sentence,
                        'form' => $row->form === null ? null : mb_substr($row->form, 0, 64),
                        'stage' => $stage,
                        'lapses' => $row->lapses,
                        'due_at' => $row->due_at,
                        'updated_at' => $row->updated_at,
                        'synced_at' => now(),
                        'deleted_at' => $row->deleted_at,
                    ]);
                }
            });
    }
};
