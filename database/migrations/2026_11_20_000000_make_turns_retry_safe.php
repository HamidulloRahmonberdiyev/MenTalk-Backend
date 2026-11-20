<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversation_turns', function (Blueprint $t) {
            // The app's own id for one learner turn, kept on the tutor's answer to it. A turn sent again
            // after a broken connection carries the same id and gets the saved answer, not a second one.
            $t->string('client_id', 40)->nullable()->after('role');

            $t->unique(['conversation_id', 'client_id']);
        });
    }

    public function down(): void
    {
        Schema::table('conversation_turns', function (Blueprint $t) {
            $t->dropUnique(['conversation_id', 'client_id']);
            $t->dropColumn('client_id');
        });
    }
};
