<?php

declare(strict_types=1);

namespace App\Modules\Mistakes\Services;

use App\Modules\Account\Models\User;
use App\Modules\Conversation\Enums\MistakeCategory;
use App\Modules\Mistakes\Models\Mistake;
use App\Modules\Vocabulary\Enums\WordStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/** Reads a learner's notebook in one language: what is in it, and what to practise next. */
final class Notebook
{
    /** @return Builder<Mistake> */
    public function of(User $user, string $language): Builder
    {
        return Mistake::query()->active()->where('user_id', $user->id)->where('language', $language);
    }

    /**
     * Everything due first, oldest due first. When fewer are due than asked for, the round is
     * topped up with the ones not mastered yet, the most stubborn first, so there is always
     * something to practise while the notebook has anything in it. Practice ahead of schedule
     * does not move the schedule (`Srs`), so topping up costs the learner nothing.
     *
     * @return Collection<int, Mistake>
     */
    public function practice(User $user, string $language, int $limit, ?CarbonImmutable $now = null): Collection
    {
        $now ??= CarbonImmutable::now();
        $due = $this->of($user, $language)->where('due_at', '<=', $now)->orderBy('due_at')->orderBy('id')->limit($limit)->get();

        if ($due->count() >= $limit) {
            return $due;
        }

        $extra = $this->of($user, $language)
            ->whereNotIn('id', $due->modelKeys())
            ->where('status', '!=', WordStatus::Known->value)
            ->orderByDesc('lapses')->orderByDesc('occurrences')->orderBy('due_at')
            ->limit($limit - $due->count())
            ->get();

        return $due->concat($extra)->values();
    }

    /**
     * @return array{total: int, due: int, learning: int, mastered: int, next_due_at: ?string, categories: object}
     */
    public function summary(User $user, string $language, ?CarbonImmutable $now = null): array
    {
        $now ??= CarbonImmutable::now();
        $rows = $this->of($user, $language)
            ->toBase()
            ->selectRaw('category, status, count(*) as total, sum(case when due_at <= ? then 1 else 0 end) as due', [$now])
            ->groupBy('category', 'status')
            ->get();

        $categories = [];
        foreach ($rows as $row) {
            $categories[$row->category] = ($categories[$row->category] ?? 0) + (int) $row->total;
        }
        // The biggest weakness first.
        arsort($categories);

        $count = fn (?WordStatus $status = null) => (int) $rows->when($status, fn ($rows) => $rows->where('status', $status->value))->sum('total');
        $nextDue = $this->of($user, $language)->where('due_at', '>', $now)->min('due_at');

        return [
            'total' => $count(),
            'due' => (int) $rows->sum('due'),
            'learning' => $count() - $count(WordStatus::Known),
            'mastered' => $count(WordStatus::Known),
            'next_due_at' => $nextDue === null ? null : CarbonImmutable::parse($nextDue)->toIso8601String(),
            // An object even when empty, so clients always read a map.
            'categories' => (object) array_intersect_key($categories, array_flip(MistakeCategory::values())),
        ];
    }
}
