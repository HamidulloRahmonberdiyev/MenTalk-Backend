<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** The leaderboard sorts every learner by XP, and sums this week's days across all learners. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learner_profiles', function (Blueprint $t) {
            $t->index('xp');
        });

        // The primary key starts with user_id, so it cannot serve a scan over one week for everyone.
        Schema::table('daily_progress', function (Blueprint $t) {
            $t->index('day');
        });
    }

    public function down(): void
    {
        Schema::table('daily_progress', function (Blueprint $t) {
            $t->dropIndex(['day']);
        });

        Schema::table('learner_profiles', function (Blueprint $t) {
            $t->dropIndex(['xp']);
        });
    }
};
