<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The devices a learner can be reached on. Each row hangs off the session that registered it, so
 * signing out (or deleting the account, which drops every session) stops the pushes by itself.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_tokens', function (Blueprint $t) {
            $t->id();
            $t->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('personal_access_token_id')->constrained()->cascadeOnDelete();
            // One row per device: a second person signing in on the same phone takes the row over.
            $t->string('token')->unique();
            $t->string('platform', 8);
            $t->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_tokens');
    }
};
