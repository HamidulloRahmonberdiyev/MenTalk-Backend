<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Onboarding now asks where the learner heard of the app. Existing learners were never asked, hence no backfill. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learner_profiles', function (Blueprint $t) {
            $t->string('referral_source', 16)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('learner_profiles', function (Blueprint $t) {
            $t->dropColumn('referral_source');
        });
    }
};
