<?php

declare(strict_types=1);

use App\Modules\Ai\Services\PromptStore;
use Database\Seeders\PromptSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * The tutor gets a personality: jokes, playful teasing and mock outrage, and it now says the main
 * mistake of a turn out loud, in one short aside in the learner's interface language.
 *
 * Deploys run migrations but not seeders. The four prompts are swapped together or not at all: a
 * half-swapped set would contradict itself (the old flow forbids exactly what the new system prompt
 * asks for). A prompt an admin has rewritten is never overwritten; the swap is skipped and logged,
 * and the new wording waits in `PromptSeeder` for them to adopt.
 */
return new class extends Migration
{
    /** The seeded wording this migration replaces, as it was. */
    private const PREVIOUS = [
        'tutor.system' => <<<'TXT'
You are {tutor}, a warm, patient {language} conversation tutor speaking with a learner inside a role-play.
Scenario: {scenario}
Possible directions to move the conversation: {topics}
About the learner: {learner}
Language level rules: {level}
Address the learner correctly: {gender}
The learner's native/interface language is {ui_language}.
{flow_rules}
{language_rules}
Always reply in {language}{script} only, never in another language. Respond with JSON only.
- "transcript": exactly what the learner said (empty if there was no learner speech yet).
- "reply": your next line in the role-play.
- "emotion": the emotion your line should be spoken with.
- "mistakes": the learner's real mistakes in this turn (said / correct / note, note written in {ui_language}).
TXT,
        'tutor.flow' => <<<'TXT'
The conversation never ends by itself. Every reply ends with a short question that keeps it going, for example {follow_ups}.
Keep replies to one to three short sentences. Never point out mistakes inside the reply; list them only in "mistakes".
Stay in your role as {tutor} and in the scenario. If the learner is silent or unclear, gently rephrase.
TXT,
        'turn.opening' => 'Start the role-play now as {tutor} with a short, friendly opening line in {language}{script} that ends with a question. The learner has not spoken yet; leave "transcript" empty.',
        'turn.audio' => 'The learner is learning {language} and answered with the attached audio. Transcribe it exactly, in the language it was spoken, into "transcript", then continue the role-play in {language}.',
    ];

    public function up(): void
    {
        $this->swap(self::PREVIOUS, self::current());
    }

    public function down(): void
    {
        $this->swap(self::current(), self::PREVIOUS);
    }

    private static function current(): array
    {
        return [
            'tutor.system' => PromptSeeder::TUTOR_SYSTEM,
            'tutor.flow' => PromptSeeder::TUTOR_FLOW,
            'turn.opening' => PromptSeeder::TURN_OPENING,
            'turn.audio' => PromptSeeder::TURN_AUDIO,
        ];
    }

    /**
     * Writes `$to` over prompts that still read `$from`, inserts missing ones (a fresh database is
     * seeded afterwards anyway), and leaves the whole set alone if any of it was edited by hand.
     */
    private function swap(array $from, array $to): void
    {
        $stored = DB::table('ai_prompts')->whereIn('key', array_keys($to))->pluck('body', 'key');

        $edited = collect($to)->keys()
            ->filter(fn (string $key) => $stored->has($key) && ! self::same($stored[$key], $from[$key]) && ! self::same($stored[$key], $to[$key]))
            ->values();

        if ($edited->isNotEmpty()) {
            Log::warning('Tutor prompts not updated: edited in the admin panel ('.$edited->implode(', ').'). Compare them with PromptSeeder and update by hand.');

            return;
        }

        foreach ($to as $key => $body) {
            if ($stored->has($key)) {
                DB::table('ai_prompts')->where('key', $key)->update(['body' => $body, 'updated_at' => now()]);
            } else {
                DB::table('ai_prompts')->insert(['key' => $key, 'body' => $body, 'active' => true, 'created_at' => now(), 'updated_at' => now()]);
            }
        }

        Cache::forget(PromptStore::CACHE_KEY);
    }

    /** Equal but for line endings and surrounding whitespace, which the admin form may change. */
    private static function same(string $a, string $b): bool
    {
        $normalize = fn (string $text): string => trim(str_replace("\r\n", "\n", $text));

        return $normalize($a) === $normalize($b);
    }
};
