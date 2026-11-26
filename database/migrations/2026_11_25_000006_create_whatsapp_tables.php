<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3 - WhatsApp.
 *
 * The queue is the important table. The legacy addon learned (v6.4) that
 * calling the gateway inside a hook held up the customer's order while the
 * gateway was slow, so automatic messages became rows a cron sends. That design
 * is kept: a hook writes a row and returns, and the queue command does the
 * network call.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_settings', function (Blueprint $table) {
            $table->id();
            $table->text('api_key')->nullable();
            $table->string('sender', 30)->nullable();
            $table->string('api_domain', 150)->default('https://wpg.softcents.com');
            $table->string('footer', 255)->nullable();
            $table->string('admin_numbers', 255)->nullable();
            $table->boolean('client_portal')->default(true);
            $table->unsignedTinyInteger('max_retry')->default(3);
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('whatsapp_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60)->unique();
            $table->string('type', 10)->default('client');
            $table->text('template')->nullable();
            $table->string('variables', 500)->nullable();
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('whatsapp_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('client_id')->nullable()->index();
            $table->string('number', 30)->index();
            $table->text('message')->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('type', 20)->default('text');
            $table->string('media_url', 500)->nullable();
            $table->string('source', 20)->default('automatic');
            $table->string('template_name', 80)->nullable();
            $table->unsignedTinyInteger('retry_count')->default(0);
            $table->string('error', 500)->nullable();
            $table->timestamp('last_retry_at')->nullable();
            $table->timestamps();
        });

        Schema::create('whatsapp_queue', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('client_id')->nullable();
            $table->string('number', 30);
            $table->text('message')->nullable();
            $table->string('type', 20)->default('text');
            $table->string('media_url', 500)->nullable();
            $table->string('media_type', 30)->nullable();
            $table->boolean('view_once')->default(false);
            $table->string('template_name', 80)->nullable();
            $table->string('status', 20)->default('pending');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->string('error', 500)->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_queue');
        Schema::dropIfExists('whatsapp_logs');
        Schema::dropIfExists('whatsapp_templates');
        Schema::dropIfExists('whatsapp_settings');
    }
};
