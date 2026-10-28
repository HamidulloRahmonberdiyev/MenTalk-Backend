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
            // An uploaded photo wins over the Google one; removing it falls back to Google again.
            $t->string('avatar_path')->nullable()->after('name');
            $t->string('google_avatar_url', 1024)->nullable()->after('avatar_path');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->dropColumn(['avatar_path', 'google_avatar_url']);
        });
    }
};
