<?php

declare(strict_types=1);

use App\Modules\Mistakes\Actions\RecordMistakes;
use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The mistakes notebook: the tutor's corrections per learner, merged by what was said and what is
 * right, each with the same review schedule as a word (`Srs`), and the log of drill answers.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_mistakes', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $t->string('language', 8);
            $t->string('said', 300);
            $t->string('correct', 300);
            $t->string('note', 300)->nullable();
            // The interface language the note was written in; a later one replaces it.
            $t->string('note_language', 2)->nullable();
            $t->string('category', 16)->default('other');
            // sha256 of the normalised "said → correct": the same mistake made again is this row.
            $t->char('fingerprint', 64);
            $t->unsignedInteger('occurrences')->default(1);
            // Where it was last made. Nulled, not cascaded: the mistake outlives a deleted conversation.
            $t->foreignUlid('conversation_id')->nullable()->constrained('conversations')->nullOnDelete();
            $t->timestampTz('first_seen_at');
            $t->timestampTz('last_seen_at');
            $t->string('status', 8)->default('new');
            $t->decimal('ease', 4, 2)->default(2.5);
            $t->unsignedSmallInteger('interval_days')->default(0);
            $t->unsignedSmallInteger('repetitions')->default(0);
            $t->unsignedSmallInteger('lapses')->default(0);
            $t->timestampTz('due_at')->useCurrent();
            $t->timestampTz('last_reviewed_at')->nullable();
            // "Not a mistake": hidden, but kept so making it again does not bring it back.
            $t->timestampTz('dismissed_at')->nullable();
            $t->timestampsTz();

            $t->unique(['user_id', 'language', 'fingerprint']);
            $t->index(['user_id', 'language', 'due_at']);
            $t->index(['user_id', 'language', 'last_seen_at']);
        });

        Schema::create('mistake_reviews', function (Blueprint $t) {
            $t->id();
            $t->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $t->foreignUlid('mistake_id')->constrained('user_mistakes')->cascadeOnDelete();
            $t->unsignedTinyInteger('rating');
            $t->string('exercise', 8)->nullable();
            $t->timestampTz('reviewed_at', 3);
            $t->unsignedInteger('duration_ms')->nullable();

            // Offline clients resend rounds; the same mistake at the same instant is the same answer.
            $t->unique(['user_id', 'mistake_id', 'reviewed_at']);
            $t->index('mistake_id');
        });

        $this->collectPastConversations();
    }

    /**
     * Learners already have corrections in their conversations: they open a notebook with those
     * in it, not an empty one. Runs here, before any new turn can reach the notebook, so nothing
     * is counted twice. Old corrections have no category; they file under "other".
     */
    private function collectPastConversations(): void
    {
        $record = app(RecordMistakes::class);

        DB::table('conversation_turns as t')
            ->join('conversations as c', 'c.id', '=', 't.conversation_id')
            ->leftJoin('learner_profiles as p', 'p.user_id', '=', 'c.user_id')
            ->where('t.role', 'learner')
            ->whereNotNull('t.mistakes')
            ->select('t.id', 't.mistakes', 't.created_at', 't.conversation_id', 'c.user_id', 'c.target_language', 'p.ui_language')
            ->chunkById(500, function ($turns) use ($record) {
                foreach ($turns as $turn) {
                    $mistakes = json_decode((string) $turn->mistakes, true);

                    if (is_array($mistakes) && $mistakes !== []) {
                        $record($turn->user_id, $turn->target_language, $turn->ui_language ?? 'uz', $turn->conversation_id, $mistakes, CarbonImmutable::parse($turn->created_at));
                    }
                }
            }, 't.id', 'id');
    }

    public function down(): void
    {
        Schema::dropIfExists('mistake_reviews');
        Schema::dropIfExists('user_mistakes');
    }
};
