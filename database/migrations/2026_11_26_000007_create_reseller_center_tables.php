<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Phase 5 - Reseller Center (excluding domain reseller features).
 *
 * Groups define commission payout tiers, permissions, and white-label limits;
 * resellers bind a PNLCS client to branding, encrypted SMTP settings, and an
 * accrued balance; end-clients link through reseller_clients so paid invoices
 * calculate idempotent commissions and allow reseller-scoped self-service.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('reseller_groups')) {
            Schema::create('reseller_groups', function (Blueprint $table) {
                $table->id();
                $table->string('name', 120);
                $table->text('description')->nullable();
                $table->json('permissions')->nullable();
                $table->string('payout_method', 32)->default('credit_balance');
                $table->string('invoicing_method', 32)->default('provider');
                $table->boolean('allow_deferred')->default(false);
                $table->decimal('credit_limit', 12, 2)->default(0);
                $table->boolean('allow_promos')->default(true);
                $table->boolean('allow_own_smtp')->default(false);
                $table->boolean('allow_suspend')->default(true);
                $table->boolean('allow_global_search')->default(true);
                $table->decimal('default_commission_percent', 6, 2)->default(10.00);
                $table->json('settings')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('resellers')) {
            Schema::create('resellers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_id')->unique();
                $table->unsignedBigInteger('group_id')->index();
                $table->string('cname', 190)->nullable();
                $table->string('company_name', 190)->nullable();
                $table->string('email_address', 190)->nullable();
                $table->string('store_template', 64)->default('standard_cart');
                $table->string('logo_url', 512)->nullable();
                $table->string('invoice_logo_url', 512)->nullable();
                $table->json('branding_settings')->nullable();
                $table->text('smtp_settings')->nullable();
                $table->decimal('balance', 14, 2)->default(0);
                $table->string('status', 24)->default('active')->index();
                $table->string('paypal_email', 190)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('reseller_clients')) {
            Schema::create('reseller_clients', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('reseller_id')->index();
                $table->unsignedBigInteger('client_id')->unique();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('reseller_pricing')) {
            Schema::create('reseller_pricing', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('group_id')->index();
                $table->string('type', 32)->default('product');
                $table->unsignedBigInteger('rel_id')->default(0);
                $table->string('billing_cycle', 32)->nullable();
                $table->decimal('min_price', 12, 2)->nullable();
                $table->decimal('max_price', 12, 2)->nullable();
                $table->string('payout_type', 32)->default('percent');
                $table->decimal('payout_value', 12, 2)->default(0);
                $table->timestamps();
                $table->index(['group_id', 'type', 'rel_id']);
            });
        }

        if (! Schema::hasTable('reseller_payouts')) {
            Schema::create('reseller_payouts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('reseller_id')->index();
                $table->unsignedBigInteger('invoice_id')->nullable()->index();
                $table->unsignedBigInteger('client_id')->nullable()->index();
                $table->decimal('amount', 14, 2);
                $table->string('payout_method', 32)->default('credit_balance');
                $table->string('status', 24)->default('pending')->index();
                $table->text('description')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('reseller_promos')) {
            Schema::create('reseller_promos', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('reseller_id')->index();
                $table->string('code', 64)->unique();
                $table->string('type', 24)->default('percent');
                $table->decimal('value', 12, 2)->default(0);
                $table->unsignedInteger('max_uses')->default(0);
                $table->unsignedInteger('uses')->default(0);
                $table->timestamp('expires_at')->nullable();
                $table->boolean('active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('reseller_invoices')) {
            Schema::create('reseller_invoices', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('reseller_id')->index();
                $table->unsignedBigInteger('invoice_id')->index();
                $table->decimal('profit', 14, 2)->default(0);
                $table->string('status', 24)->default('pending');
                $table->timestamps();
                $table->unique(['reseller_id', 'invoice_id']);
            });
        }

        if (! Schema::hasTable('reseller_gateways')) {
            Schema::create('reseller_gateways', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('reseller_id')->index();
                $table->string('gateway', 48);
                $table->text('settings')->nullable();
                $table->boolean('enabled')->default(true);
                $table->timestamps();
                $table->unique(['reseller_id', 'gateway']);
            });
        }

        if (! Schema::hasTable('reseller_email_templates')) {
            Schema::create('reseller_email_templates', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('reseller_id')->index();
                $table->string('template_name', 120);
                $table->string('subject', 255)->nullable();
                $table->text('body')->nullable();
                $table->boolean('enabled')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('reseller_logs')) {
            Schema::create('reseller_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('reseller_id')->nullable()->index();
                $table->unsignedBigInteger('admin_id')->nullable();
                $table->string('action', 80);
                $table->text('description')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reseller_logs');
        Schema::dropIfExists('reseller_email_templates');
        Schema::dropIfExists('reseller_gateways');
        Schema::dropIfExists('reseller_invoices');
        Schema::dropIfExists('reseller_promos');
        Schema::dropIfExists('reseller_payouts');
        Schema::dropIfExists('reseller_pricing');
        Schema::dropIfExists('reseller_clients');
        Schema::dropIfExists('resellers');
        Schema::dropIfExists('reseller_groups');
    }
};
