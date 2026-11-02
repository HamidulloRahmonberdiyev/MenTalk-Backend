<?php

declare(strict_types=1);

namespace App\Actions\Vocabulary;

use App\DTO\NewWord;
use App\Enums\WordSource;
use App\Models\User;
use App\Models\UserWord;
use App\Services\Vocabulary\Dictionary;
use Illuminate\Support\Facades\DB;

final readonly class AddWordToUser
{
    public function __construct(private Dictionary $dictionary) {}

    /**
     * Puts a word on the learner's list, adding it to the dictionary if it is new there. A word
     * already on the list is returned untouched; one the learner deleted comes back with its history.
     */
    public function __invoke(User $user, NewWord $new, WordSource $source): UserWord
    {
        return DB::transaction(function () use ($user, $new, $source) {
            $word = $this->dictionary->entry($new->language, $new->text, $new->pos, $user);
            $translation = $new->translation !== null && $new->translationLanguage !== null
                ? $this->dictionary->translate($word, $new->translationLanguage, $new->translation, $user)
                : null;

            $userWord = UserWord::withTrashed()->createOrFirst(['user_id' => $user->id, 'word_id' => $word->id], [
                'translation_word_id' => $translation?->id,
                'source' => $source,
                'context_sentence' => $new->contextSentence,
                'form' => $new->form,
                'due_at' => now(),
                'synced_at' => now(),
            ]);

            if ($userWord->trashed()) {
                $userWord->forceFill([
                    'deleted_at' => null,
                    'translation_word_id' => $translation?->id ?? $userWord->translation_word_id,
                    'source' => $source,
                    'context_sentence' => $new->contextSentence ?? $userWord->context_sentence,
                    'form' => $new->form ?? $userWord->form,
                    'synced_at' => now(),
                ])->save();
            }

            return $userWord;
        });
    }
}
