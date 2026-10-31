<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Onboarding now asks how the learner studied before and what they find hard, and can measure the
 * level with a short placement test. Existing learners chose their level by hand, hence no backfill.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learner_profiles', function (Blueprint $t) {
            $t->string('experience', 16)->nullable();
            $t->jsonb('focus')->nullable();
            $t->string('level_source', 12)->nullable();
            $t->unsignedTinyInteger('placement_score')->nullable();
            $t->timestamp('placed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('learner_profiles', function (Blueprint $t) {
            $t->dropColumn(['experience', 'focus', 'level_source', 'placement_score', 'placed_at']);
        });
    }
};
