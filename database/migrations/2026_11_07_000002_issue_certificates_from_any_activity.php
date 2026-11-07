<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Certificates no longer need the placement test and five conversations: any activity earns one,
 * and the level follows from how much there is. So a certificate may lack a measured skill (no
 * conversation, no speaking score) or the test, records the word list it counted, and a learner
 * may hold several for one level, each a dated snapshot of their progress.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $t) {
            // Added before the unique key goes: MySQL needs an index starting with user_id for its foreign key.
            $t->index(['user_id', 'language', 'issued_at']);
        });

        Schema::table('certificates', function (Blueprint $t) {
            $t->dropUnique(['user_id', 'language', 'level']);
            $t->unsignedTinyInteger('placement_score')->nullable()->change();
            $t->decimal('speaking', 3, 1)->nullable()->change();
            $t->decimal('vocabulary', 3, 1)->nullable()->change();
            $t->decimal('grammar', 3, 1)->nullable()->change();
            $t->unsignedInteger('words')->default(0)->after('conversations');
        });
    }

    /** Scores stay nullable: certificates issued meanwhile may lack them, and an issued one is never altered. */
    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $t) {
            $t->dropColumn('words');
            $t->unique(['user_id', 'language', 'level']);
        });

        Schema::table('certificates', function (Blueprint $t) {
            $t->dropIndex(['user_id', 'language', 'issued_at']);
        });
    }
};
