<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learner_profiles', function (Blueprint $t) {
            $t->ulid('user_id')->primary();
            $t->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $t->string('gender', 12)->nullable();
            $t->char('country', 2)->nullable();
            $t->date('birth_date')->nullable();
            $t->string('level', 16)->nullable();
            $t->jsonb('goals')->nullable();
            $t->char('ui_language', 2)->default('uz');
            $t->string('timezone', 40)->default('Asia/Tashkent');
            $t->unsignedSmallInteger('daily_goal_minutes')->default(10);
            $t->unsignedInteger('xp')->default(0);
            $t->unsignedSmallInteger('streak')->default(0);
            $t->date('last_active_on')->nullable();
        });

        Schema::create('scenarios', function (Blueprint $t) {
            $t->string('id', 24)->primary();
            $t->string('icon', 40);
            $t->string('tint', 9);
            $t->jsonb('gradient');
            $t->string('image_path')->nullable();
            $t->jsonb('title');
            $t->jsonb('subtitle');
            $t->text('brief');
            $t->text('next_topics');
            $t->unsignedSmallInteger('sort')->default(0);
            $t->boolean('active')->default(true);
            $t->timestamps();
        });

        Schema::create('ai_prompts', function (Blueprint $t) {
            $t->id();
            $t->string('key', 64)->unique();
            $t->text('body');
            $t->boolean('active')->default(true);
            $t->timestamps();
        });

        Schema::create('conversations', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->ulid('user_id');
            $t->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $t->string('scenario_id', 24);
            $t->foreign('scenario_id')->references('id')->on('scenarios');
            $t->string('state', 12)->default('active');
            $t->unsignedSmallInteger('learner_turns')->default(0);
            $t->unsignedInteger('duration_sec')->default(0);
            $t->unsignedTinyInteger('score')->nullable();
            $t->timestampTz('started_at');
            $t->timestampTz('ended_at')->nullable();
            $t->index(['user_id', 'started_at']);
        });

        Schema::create('conversation_turns', function (Blueprint $t) {
            $t->id();
            $t->ulid('conversation_id');
            $t->foreign('conversation_id')->references('id')->on('conversations')->cascadeOnDelete();
            $t->string('role', 8);
            $t->text('text');
            $t->string('emotion', 16)->nullable();
            $t->jsonb('mistakes')->nullable();
            $t->string('audio_url')->nullable();
            $t->timestampTz('created_at')->useCurrent();
            $t->index(['conversation_id', 'id']);
        });

        Schema::create('conversation_results', function (Blueprint $t) {
            $t->ulid('conversation_id')->primary();
            $t->foreign('conversation_id')->references('id')->on('conversations')->cascadeOnDelete();
            $t->unsignedTinyInteger('speech');
            $t->unsignedTinyInteger('vocabulary');
            $t->unsignedTinyInteger('grammar');
            $t->unsignedTinyInteger('overall');
            $t->jsonb('mistakes');
            $t->jsonb('new_words');
        });

        Schema::create('vocab_cards', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->ulid('user_id');
            $t->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $t->string('word', 64);
            $t->string('translation', 160);
            $t->text('example')->nullable();
            $t->string('form', 64)->nullable();
            $t->unsignedTinyInteger('stage')->default(0);
            $t->unsignedSmallInteger('streak')->default(0);
            $t->unsignedSmallInteger('lapses')->default(0);
            $t->timestampTz('due_at');
            $t->timestampTz('updated_at');
            $t->timestampTz('synced_at');
            $t->softDeletes();
            $t->unique(['user_id', 'word']);
            $t->index(['user_id', 'synced_at']);
            $t->index(['user_id', 'due_at']);
        });

        Schema::create('daily_progress', function (Blueprint $t) {
            $t->ulid('user_id');
            $t->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $t->date('day');
            $t->unsignedSmallInteger('minutes')->default(0);
            $t->unsignedInteger('xp')->default(0);
            $t->primary(['user_id', 'day']);
        });

        Schema::create('ai_usage', function (Blueprint $t) {
            $t->id();
            $t->ulid('user_id')->nullable()->index();
            $t->string('kind', 24);
            $t->unsignedInteger('latency_ms');
            $t->unsignedSmallInteger('status');
            $t->timestampTz('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        foreach (['ai_usage', 'daily_progress', 'vocab_cards', 'conversation_results', 'conversation_turns', 'conversations', 'ai_prompts', 'scenarios', 'learner_profiles'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
