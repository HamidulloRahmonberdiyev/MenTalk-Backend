<?php

declare(strict_types=1);

namespace App\Modules\Progress\Http\Requests;

use App\Modules\Progress\Enums\LeaderboardPeriod;
use App\Modules\Progress\Services\Leaderboard;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ShowLeaderboardRequest extends FormRequest
{
    private const DEFAULT_LIMIT = 20;

    public function rules(): array
    {
        return [
            'period' => ['nullable', Rule::enum(LeaderboardPeriod::class)],
            'limit' => ['nullable', 'integer', 'min:1', 'max:'.Leaderboard::MAX_LIMIT],
        ];
    }

    public function period(): LeaderboardPeriod
    {
        return LeaderboardPeriod::tryFrom((string) $this->validated('period')) ?? LeaderboardPeriod::Week;
    }

    public function limit(): int
    {
        return (int) ($this->validated('limit') ?? self::DEFAULT_LIMIT);
    }
}
