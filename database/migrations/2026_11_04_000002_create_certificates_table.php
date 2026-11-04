<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A certificate records what the learner had reached on the day it was issued, so the name, level
 * and scores are copied here: later practice or a renamed account must not change an issued document.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $t->string('serial', 20)->unique();
            $t->string('language', 8);
            $t->string('level', 16);
            $t->string('holder_name', 120);
            $t->unsignedTinyInteger('placement_score');
            $t->decimal('speaking', 3, 1);
            $t->decimal('vocabulary', 3, 1);
            $t->decimal('grammar', 3, 1);
            $t->decimal('overall', 3, 1);
            $t->unsignedSmallInteger('conversations');
            $t->timestampTz('issued_at');
            // One per level: reaching the next level is what earns a new certificate.
            $t->unique(['user_id', 'language', 'level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
