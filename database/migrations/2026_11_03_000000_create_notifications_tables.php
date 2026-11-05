<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The in-app inbox.
 *
 * `notifications` keeps Laravel's database-channel shape, so `$user->notify()` writes to it and a
 * push channel can be added later without touching the table. `data` holds the message as
 * translation keys and parameters (or per-language text for announcements), not finished
 * sentences: it is put into words when read, in the language the learner uses at that moment.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $t) {
            $t->uuid('id')->primary();
            // A stable name (App\Modules\Notification\Enums\NotificationType), not a class name, so refactors keep old rows readable.
            $t->string('type', 32);
            $t->ulidMorphs('notifiable');
            $t->json('data');
            $t->timestampTz('read_at')->nullable();
            $t->timestampsTz();

            // The inbox lists newest first per learner; reminders ask "already sent today?" by type.
            $t->index(['notifiable_id', 'created_at']);
            $t->index(['notifiable_id', 'type', 'created_at']);
            $t->index('created_at');
        });

        // Messages written in the admin panel and sent to many learners at once.
        Schema::create('announcements', function (Blueprint $t) {
            $t->id();
            $t->string('kind', 16);
            $t->json('title');
            $t->json('body');
            $t->string('action', 16)->nullable();
            // An interface language to send to; null is everyone.
            $t->char('audience', 2)->nullable();
            $t->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            // Set when an admin presses "send"; the record is frozen from then on.
            $t->timestampTz('dispatched_at')->nullable();
            $t->timestampTz('sent_at')->nullable();
            $t->unsignedInteger('recipients')->default(0);
            $t->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('notifications');
    }
};
