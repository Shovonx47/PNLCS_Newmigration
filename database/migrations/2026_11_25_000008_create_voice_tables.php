<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3 - automated voice calls.
 *
 * The 24 events, the per-product-group audio/caller overrides, the calling
 * window, and the retry queue that keeps a meaningful call out of the "it rang
 * once at 2am" category.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voice_settings', function (Blueprint $table) {
            $table->id();
            $table->text('api_token')->nullable();
            $table->string('api_base_url', 150)->default('https://vsms.softcents.com/api');
            $table->string('caller_id', 30)->default('376');
            $table->string('calling_start', 5)->default('09:00');
            $table->string('calling_end', 5)->default('21:00');
            $table->string('timezone', 40)->default('Asia/Dhaka');
            $table->boolean('master_paused')->default(true);
            $table->boolean('retry_enabled')->default(true);
            $table->unsignedSmallInteger('retry_interval_minutes')->default(15);
            $table->unsignedTinyInteger('max_attempts')->default(3);
            $table->timestamps();
        });

        Schema::create('voice_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_key', 60)->unique();
            $table->string('category', 20)->default('billing');
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->string('audio_id', 50)->nullable();
            $table->string('caller_id', 50)->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->timestamps();
        });

        Schema::create('voice_group_overrides', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('group_id');
            $table->string('event_key', 60);
            $table->string('audio_id', 50)->nullable();
            $table->string('caller_id', 50)->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->unique(['group_id', 'event_key']);
        });

        Schema::create('voice_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('client_id')->nullable()->index();
            $table->unsignedInteger('invoice_id')->nullable()->index();
            $table->string('phone', 30);
            $table->string('event_key', 60);
            $table->string('audio_id', 50)->nullable();
            $table->string('caller_id', 50)->nullable();
            $table->string('call_id', 60)->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('reason', 300)->nullable();
            $table->unsignedTinyInteger('attempt')->default(1);
            $table->timestamps();
        });

        Schema::create('voice_retries', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('client_id')->nullable();
            $table->string('phone', 30);
            $table->string('event_key', 60);
            $table->unsignedInteger('group_id')->nullable();
            $table->string('audio_id', 50);
            $table->string('caller_id', 50);
            $table->string('initial_call_id', 60)->nullable();
            $table->unsignedTinyInteger('attempt')->default(1);
            $table->unsignedTinyInteger('max_attempts')->default(3);
            $table->unsignedSmallInteger('interval_minutes')->default(15);
            $table->string('status', 30)->default('pending');
            $table->string('last_status_reason', 300)->nullable();
            $table->dateTime('next_attempt_at')->nullable()->index();
            $table->dateTime('completed_at')->nullable();
            $table->text('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voice_retries');
        Schema::dropIfExists('voice_logs');
        Schema::dropIfExists('voice_group_overrides');
        Schema::dropIfExists('voice_events');
        Schema::dropIfExists('voice_settings');
    }
};
