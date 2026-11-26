<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3 - the client Telegram bot.
 *
 * One row per customer. The legacy table held a link token forever, even after
 * the customer had connected, and a chat_id column nothing enforced: two rows
 * could name the same chat. Here the token is unique, the chat is unique, and a
 * customer has one row.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_clients', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('client_id')->unique();
            $table->string('chat_id', 64)->nullable()->unique();
            $table->string('telegram_username', 100)->nullable();
            $table->string('telegram_name', 150)->nullable();
            $table->string('link_token', 40)->unique();
            $table->boolean('notify_enabled')->default(true);
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });

        Schema::create('telegram_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('client_id')->nullable()->index();
            $table->string('chat_id', 64)->nullable();
            $table->string('direction', 10)->default('out');
            $table->string('event', 60)->nullable();
            $table->text('body')->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('error', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_messages');
        Schema::dropIfExists('telegram_clients');
    }
};
