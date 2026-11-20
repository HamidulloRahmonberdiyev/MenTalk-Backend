<?php

declare(strict_types=1);

namespace App\Modules\Conversation\Http\Controllers;

use App\Modules\Ai\Exceptions\AiUnavailable;
use App\Modules\Conversation\Actions\StreamTurn;
use App\Modules\Conversation\DTO\TurnInput;
use App\Modules\Conversation\Enums\TurnKind;
use App\Modules\Conversation\Http\Requests\StoreTurnRequest;
use App\Modules\Conversation\Models\Conversation;
use App\Modules\Conversation\Services\TurnHistory;
use App\Shared\Http\Controller;
use App\Shared\Http\Middleware\DailyQuota;
use App\Shared\Http\Problem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class StreamTurnController extends Controller
{
    private const LOCK_SECONDS = 40;

    public function __invoke(StoreTurnRequest $request, Conversation $conversation, StreamTurn $turn, TurnHistory $history): Response
    {
        Gate::authorize('update', $conversation);
        $input = $request->toInput();
        $clips = $request->boolean('clips');

        // Taken before the stream opens, so a turn already running is a plain 409 and not a broken stream.
        // A retry that arrives while its first copy is still running waits here, then finds it answered.
        $lock = Cache::lock("conversation:{$conversation->id}", self::LOCK_SECONDS);
        $lock->block(3);

        $answered = $history->answered($conversation, $input);

        if ($answered === null && ! $this->charge($input, (string) $request->user()->id)) {
            $lock->release();

            return Problem::make(429, 'quota');
        }

        return response()->stream(function () use ($turn, $conversation, $input, $clips, $lock, $answered): void {
            // A learner who leaves mid-turn still gets the turn saved; only the voice is skipped then.
            ignore_user_abort(true);

            $released = false;
            $release = function () use ($lock, &$released): void {
                if (! $released) {
                    $released = true;
                    $lock->release();
                }
            };

            try {
                if ($answered !== null) {
                    $release();
                    $turn->replay($conversation, $answered, $input, $clips, $this->emitter(...));
                } else {
                    $turn($conversation, $input, $clips, $this->emitter(...), $release);
                }
            } catch (Throwable $e) {
                report($e);
                $this->emitter('error', ['status' => $e instanceof AiUnavailable ? 503 : 502]);
            } finally {
                $release();
            }
        }, 200, [
            'Content-Type' => 'text/event-stream; charset=utf-8',
            'Cache-Control' => 'no-cache, no-transform',
            // nginx would otherwise hold the events back until the buffer fills.
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /** One turn of today's allowance; the opening was paid for when the conversation was started. */
    private function charge(TurnInput $input, string $userId): bool
    {
        return $input->kind === TurnKind::Opening || DailyQuota::consume('turns', $userId);
    }

    /** @param  array<string, mixed>  $data */
    private function emitter(string $event, array $data): void
    {
        echo "event: {$event}\ndata: ".json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n\n";

        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }
}
