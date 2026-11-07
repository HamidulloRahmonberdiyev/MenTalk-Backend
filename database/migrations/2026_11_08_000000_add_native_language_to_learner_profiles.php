<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The learner's mother tongue, asked during onboarding. Nullable: learners who joined before it
 * was asked simply have none, and the tutor falls back to the interface language as before.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learner_profiles', function (Blueprint $t) {
            $t->string('native_language', 8)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('learner_profiles', function (Blueprint $t) {
            $t->dropColumn('native_language');
        });
    }
};
