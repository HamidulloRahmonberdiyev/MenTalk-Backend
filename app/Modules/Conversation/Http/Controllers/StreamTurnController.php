<?php

declare(strict_types=1);

namespace App\Modules\Conversation\Http\Controllers;

use App\Modules\Ai\Exceptions\AiUnavailable;
use App\Modules\Conversation\Actions\StreamTurn;
use App\Modules\Conversation\Http\Requests\StoreTurnRequest;
use App\Modules\Conversation\Models\Conversation;
use App\Shared\Http\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

final class StreamTurnController extends Controller
{
    private const LOCK_SECONDS = 40;

    public function __invoke(StoreTurnRequest $request, Conversation $conversation, StreamTurn $turn): StreamedResponse
    {
        Gate::authorize('update', $conversation);
        $input = $request->toInput();
        $clips = $request->boolean('clips');

        // Taken before the stream opens, so a turn already running is a plain 409 and not a broken stream.
        $lock = Cache::lock("conversation:{$conversation->id}", self::LOCK_SECONDS);
        $lock->block(3);

        return response()->stream(function () use ($turn, $conversation, $input, $clips, $lock): void {
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
                $turn($conversation, $input, $clips, $this->emitter(...), $release);
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
