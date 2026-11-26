<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Phase 2 - BDPBX daily accounting.
 *
 * One row per snapshot date per server (server_id 0 is the combined figure),
 * written by pnlcs:bdpbx-accounting. The date is kept so a past day's revenue
 * can be read back exactly as it stood instead of being recomputed from
 * today's data.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bdpbx_daily_stats')) {
            return;
        }

        Schema::create('bdpbx_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->date('snapshot_date');
            $table->unsignedBigInteger('server_id')->default(0);
            $table->string('server_name', 190)->nullable();
            $table->string('server_hostname', 190)->nullable();
            $table->unsignedInteger('total_services')->default(0);
            $table->unsignedInteger('active_services')->default(0);
            $table->unsignedInteger('suspended_services')->default(0);
            $table->unsignedInteger('pending_services')->default(0);
            $table->unsignedInteger('cancelled_services')->default(0);
            $table->decimal('active_mrr', 14, 2)->default(0);
            $table->unsignedInteger('paid_clients_month')->default(0);
            $table->unsignedInteger('paid_invoices_month')->default(0);
            $table->decimal('paid_amount_month', 14, 2)->default(0);
            $table->decimal('paid_today_amount', 14, 2)->default(0);
            $table->unsignedInteger('due_clients')->default(0);
            $table->unsignedInteger('due_invoices')->default(0);
            $table->decimal('due_amount', 14, 2)->default(0);
            // Drill-down kept with the snapshot, so a past day opens as it was
            // recorded rather than as today's data recomputed.
            $table->json('due_details')->nullable();
            $table->json('services_details')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->timestamps();
            $table->unique(['snapshot_date', 'server_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bdpbx_daily_stats');
    }
};
