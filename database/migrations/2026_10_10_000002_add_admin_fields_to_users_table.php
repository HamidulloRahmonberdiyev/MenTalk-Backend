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
            // Learners sign in with Google and never have a password; only admins do.
            $t->string('password')->nullable()->after('name');
            $t->rememberToken()->after('password');
            $t->boolean('is_admin')->default(false)->after('remember_token');
            $t->timestampTz('blocked_at')->nullable()->after('is_admin');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->dropColumn(['password', 'remember_token', 'is_admin', 'blocked_at']);
        });
    }
};
