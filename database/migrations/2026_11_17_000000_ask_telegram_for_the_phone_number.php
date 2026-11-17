<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            // E.164 ("+998901234567"), as Telegram verified it at the last sign-in. Not unique: numbers get
            // reassigned, and an account is identified by its Telegram id, not by the number it had.
            $t->string('phone', 16)->nullable()->after('email');

            $t->index('phone');
        });

        Schema::table('telegram_logins', function (Blueprint $t) {
            // The bot looks up "the request this person is answering" on every message they send.
            $t->index(['telegram_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('telegram_logins', function (Blueprint $t) {
            $t->dropIndex(['telegram_user_id', 'status']);
        });

        Schema::table('users', function (Blueprint $t) {
            $t->dropIndex(['phone']);
            $t->dropColumn('phone');
        });
    }
};
