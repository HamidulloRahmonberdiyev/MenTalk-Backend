<?php

declare(strict_types=1);

namespace App\Modules\Certificate\Services;

use App\Modules\Account\Enums\Level;

/**
 * A level worked out from whatever evidence a learner has, weighted by how much it proves:
 *
 *  - the placement test: a measured level, weight `placement_weight`;
 *  - rated conversations: the average score of recent ones, weight growing with their number
 *    (`conversation_weight` each, up to `recent_conversations`), so many chats outweigh one test;
 *  - the word list: breadth of vocabulary, only when there is nothing else, as it says little
 *    about speaking.
 *
 * Thin evidence cannot claim much: without a conversation or the test the level stays at
 * `Level::Elementary` at most, and `Level::Advanced` needs `min_conversations` rated chats.
 * Rounding is conservative (an average of 1.5 is not yet 2): a certificate must not overstate.
 */
final readonly class LevelEstimate
{
    private const LEVELS = [Level::Beginner, Level::Elementary, Level::Intermediate, Level::Advanced];

    public function __construct(private array $config) {}

    public static function fromConfig(): self
    {
        return new self(config('certificate'));
    }

    /** Null when there is no evidence at all: nothing to certify. */
    public function __invoke(?Level $placement, int $conversations, ?float $conversationOverall, int $words): ?Level
    {
        $evidence = [];

        if ($placement !== null) {
            $evidence[] = [self::index($placement), $this->config['placement_weight']];
        }

        if ($conversations > 0 && $conversationOverall !== null) {
            $counted = min($conversations, $this->config['recent_conversations']);
            $evidence[] = [$this->fromScore($conversationOverall), $this->config['conversation_weight'] * $counted];
        }

        // The list shows how much a learner used it, not how many words they know: a short list must
        // not drag down a level the test or conversations measured. It decides only on its own.
        if ($words > 0 && $evidence === []) {
            $evidence[] = [$this->fromWords($words), 1.0];
        }

        if ($evidence === []) {
            return null;
        }

        $weight = array_sum(array_column($evidence, 1));
        $average = array_sum(array_map(fn (array $item) => $item[0] * $item[1], $evidence)) / $weight;
        $index = (int) floor($average + 0.4);

        if ($placement === null && $conversations === 0) {
            $index = min($index, self::index(Level::Elementary));
        }

        if ($conversations < $this->config['min_conversations']) {
            $index = min($index, self::index(Level::Intermediate));
        }

        return self::LEVELS[max(0, min($index, 3))];
    }

    private static function index(Level $level): int
    {
        return (int) array_search($level, self::LEVELS, true);
    }

    /** A 0-10 conversation score as a level, by `score_thresholds`. */
    private function fromScore(float $score): int
    {
        return self::count($score, $this->config['score_thresholds']);
    }

    /** Words on the learner's list as a level, by `word_thresholds`. */
    private function fromWords(int $words): int
    {
        return self::count($words, $this->config['word_thresholds']);
    }

    /** How many of the ascending thresholds `$value` reaches. */
    private static function count(float|int $value, array $thresholds): int
    {
        return count(array_filter($thresholds, fn ($threshold) => $value >= $threshold));
    }
}
