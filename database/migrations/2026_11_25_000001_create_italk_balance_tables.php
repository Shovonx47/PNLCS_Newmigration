<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Phase 2 - IPTSP live balance (iTalk / ICC Talk).
 *
 * An endpoint is a provider's balance URL (the legacy URL Manager); a service
 * mapping says which customer service holds which IP number against which
 * endpoint. The balance is cached on the mapping row so the service page can
 * show the last known figure without calling the provider on every render.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('italk_balance_endpoints')) {
            Schema::create('italk_balance_endpoints', function (Blueprint $table) {
                $table->id();
                $table->string('label', 190);
                $table->text('base_url');
                // italk: sip.italk.com.bd style; icc: icctalk.com style. The
                // program decides which product list a mapping may use.
                $table->string('program', 20)->default('italk');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('italk_balance_services')) {
            Schema::create('italk_balance_services', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_id')->index();
                $table->unsignedBigInteger('service_id')->unique();
                $table->unsignedBigInteger('product_id')->index();
                $table->string('ip_number', 64)->nullable();
                $table->string('balance', 32)->nullable();
                $table->unsignedBigInteger('endpoint_id')->nullable()->index();
                $table->timestamp('last_checked_at')->nullable();
                $table->string('last_error', 255)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('italk_balance_services');
        Schema::dropIfExists('italk_balance_endpoints');
    }
};
