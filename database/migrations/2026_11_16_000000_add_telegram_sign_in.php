<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A Telegram account has neither a Google id nor an email.
        Schema::table('users', function (Blueprint $t) {
            $t->string('google_id')->nullable()->change();
            $t->string('email')->nullable()->change();
            $t->unsignedBigInteger('telegram_id')->nullable()->unique()->after('google_id');
            $t->string('telegram_username', 32)->nullable()->after('telegram_id');
            // The Telegram profile photo, copied to the avatars disk: Telegram's own file links carry the bot token.
            $t->string('telegram_avatar_path')->nullable()->after('google_avatar_url');
        });

        Schema::create('telegram_logins', function (Blueprint $t) {
            $t->ulid('id')->primary();
            // Both secrets are stored as SHA-256: the code travels through Telegram, the secret stays on the phone.
            $t->char('code_hash', 64)->unique();
            $t->char('secret_hash', 64);
            $t->string('status', 16);
            $t->string('device', 120);
            $t->string('language', 5)->nullable();
            $t->unsignedBigInteger('telegram_user_id')->nullable();
            $t->bigInteger('chat_id')->nullable();
            $t->unsignedBigInteger('message_id')->nullable();
            $t->foreignUlid('user_id')->nullable()->constrained()->cascadeOnDelete();
            $t->timestamp('expires_at');
            $t->timestamp('approved_at')->nullable();
            $t->timestamps();

            $t->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_logins');

        Schema::table('users', function (Blueprint $t) {
            $t->dropUnique(['telegram_id']);
            $t->dropColumn(['telegram_id', 'telegram_username', 'telegram_avatar_path']);
        });
    }
};
