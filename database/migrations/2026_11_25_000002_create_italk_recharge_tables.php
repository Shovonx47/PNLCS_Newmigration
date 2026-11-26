<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Phase 2 - multi-provider IPTSP auto-recharge.
 *
 * Provider credentials are the same three shapes the legacy addon supported:
 * a browser scraper (iTalk/ICC SSO), Ranksitt's REST API and WebVoice's REST
 * API. Credentials are encrypted at rest by the model casts, never written in
 * plaintext like the WHMCS tables they came from.
 *
 * Every attempt is written to italk_recharge_logs before the provider is
 * called, so a failed or interrupted top-up is visible and retryable instead
 * of silently lost.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('italk_recharge_providers')) {
            Schema::create('italk_recharge_providers', function (Blueprint $table) {
                $table->id();
                $table->string('name', 190);
                $table->string('base_url', 255);
                $table->string('base_path', 100)->default('/iconiptsp/');
                // scraper | rest
                $table->string('integration_type', 20)->default('scraper');
                // italk | ranksitt | webvoice - which driver performs the top-up
                $table->string('api_type', 20)->default('italk');
                $table->string('username', 190);
                $table->text('password')->nullable();
                $table->text('api_key')->nullable();
                $table->string('status', 20)->default('active');
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('italk_recharge_accounts')) {
            Schema::create('italk_recharge_accounts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_id')->index();
                $table->unsignedBigInteger('provider_id')->index();
                $table->string('pin_number', 64);
                $table->decimal('total_recharged', 12, 2)->default(0);
                $table->timestamp('last_recharge')->nullable();
                $table->string('status', 20)->default('active');
                $table->timestamps();
                $table->unique(['client_id', 'provider_id']);
            });
        }

        if (! Schema::hasTable('italk_recharge_logs')) {
            Schema::create('italk_recharge_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_id')->index();
                $table->unsignedBigInteger('provider_id')->nullable()->index();
                $table->unsignedBigInteger('account_id')->nullable()->index();
                $table->unsignedBigInteger('invoice_id')->nullable()->index();
                $table->string('pin_number', 64)->nullable();
                $table->decimal('amount', 12, 2);
                // pending | success | failed | skipped
                $table->string('status', 20)->default('pending');
                $table->unsignedInteger('attempts')->default(0);
                $table->timestamp('last_attempt_at')->nullable();
                $table->text('api_response')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('italk_recharge_logs');
        Schema::dropIfExists('italk_recharge_accounts');
        Schema::dropIfExists('italk_recharge_providers');
    }
};
