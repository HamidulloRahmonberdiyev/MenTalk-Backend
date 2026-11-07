<?php

declare(strict_types=1);

namespace App\Modules\Pronunciation\Actions;

use App\Modules\Account\Models\User;
use App\Modules\Language\DTO\TutorLanguage;
use App\Modules\Pronunciation\Enums\PhraseLevel;
use App\Modules\Pronunciation\Models\Phrase;
use App\Shared\Http\Middleware\DailyQuota;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * The next lines for a learner to read: never one they were given before while the pool still has
 * others. In order of cost:
 *
 *  1. lines from the shared pool this learner has not seen (free);
 *  2. when those run out, one AI batch added to the pool for everyone (one call, many lines);
 *  3. when the AI cannot be asked (daily cap, outage), the lines this learner saw longest ago.
 *
 * Lines are marked seen as they are handed out; the app keeps them queued until they are read.
 */
final readonly class NextPhrases
{
    public function __construct(private GeneratePhrases $generate) {}

    /** @return Collection<int, Phrase> */
    public function __invoke(User $user, TutorLanguage $language, PhraseLevel $level, int $limit): Collection
    {
        $phrases = $this->unseen($user, $language, $level, $limit);

        if ($phrases->count() < $limit) {
            $this->growPool($user, $language, $level, $limit);
            $phrases = $this->unseen($user, $language, $level, $limit);
        }

        if ($phrases->count() < $limit) {
            $phrases = $phrases->concat($this->seenLongestAgo($user, $language, $level, $limit - $phrases->count()));
        }

        $this->markSeen($user, $phrases);

        return $phrases;
    }

    private function pool(TutorLanguage $language, PhraseLevel $level): Builder
    {
        return Phrase::query()->where(['language' => $language->code, 'level' => $level, 'active' => true]);
    }

    /** @return Collection<int, Phrase> */
    private function unseen(User $user, TutorLanguage $language, PhraseLevel $level, int $limit): Collection
    {
        return $this->pool($language, $level)
            ->whereNotExists(fn ($seen) => $seen->from('pronunciation_phrase_views')
                ->whereColumn('phrase_id', 'pronunciation_phrases.id')
                ->where('user_id', $user->id))
            ->inRandomOrder()->limit($limit)->get();
    }

    /** @return Collection<int, Phrase> */
    private function seenLongestAgo(User $user, TutorLanguage $language, PhraseLevel $level, int $limit): Collection
    {
        return $this->pool($language, $level)
            ->join('pronunciation_phrase_views as seen', 'seen.phrase_id', '=', 'pronunciation_phrases.id')
            ->where('seen.user_id', $user->id)
            ->orderBy('seen.seen_at')->orderBy('pronunciation_phrases.id')
            ->limit($limit)->get(['pronunciation_phrases.*']);
    }

    /**
     * One batch per language and level at a time: learners who run out together wait for the same
     * batch instead of each paying for one. Only the learner whose request actually calls the AI
     * spends today's allowance.
     */
    private function growPool(User $user, TutorLanguage $language, PhraseLevel $level, int $limit): void
    {
        try {
            Cache::lock("phrases:generate:{$language->code}:{$level->value}", 60)->block(10, function () use ($user, $language, $level, $limit) {
                if ($this->unseen($user, $language, $level, $limit)->count() >= $limit) {
                    return; // Someone else's batch arrived while we waited.
                }

                if (DailyQuota::consume('phrases', $user->id)) {
                    ($this->generate)($language, $level);
                }
            });
        } catch (LockTimeoutException) {
            // Another batch is still being written; repeats cover this request.
        } catch (Throwable $e) {
            // The AI is down or answered badly: repeats are better than an error screen.
            report($e);
        }
    }

    /** @param Collection<int, Phrase> $phrases */
    private function markSeen(User $user, Collection $phrases): void
    {
        if ($phrases->isEmpty()) {
            return;
        }

        $now = now();

        DB::table('pronunciation_phrase_views')->upsert(
            $phrases->map(fn (Phrase $phrase) => ['user_id' => $user->id, 'phrase_id' => $phrase->id, 'seen_at' => $now])->all(),
            ['user_id', 'phrase_id'],
            ['seen_at'],
        );
    }
}
