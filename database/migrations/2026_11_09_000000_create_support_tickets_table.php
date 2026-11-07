<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Messages learners send from the app to the team, read and answered in the admin panel. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $t) {
            $t->id();
            $t->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $t->string('category', 16);
            $t->text('message');
            // How to reach the learner if not by the account's e-mail: a phone, a Telegram handle.
            $t->string('contact', 120)->nullable();
            $t->string('status', 16)->default('new');
            $t->text('admin_note')->nullable();
            // The device it came from, filled in by the app: most bug reports are about one platform.
            $t->string('app_version', 20)->nullable();
            $t->string('platform', 16)->nullable();
            $t->string('os_version', 32)->nullable();
            $t->string('ui_language', 2)->nullable();
            $t->timestamps();
            $t->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
