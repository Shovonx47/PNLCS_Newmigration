<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Phase 2 - client-requested IP number recharge.
 *
 * A customer asks for a top-up from the client area; PNLCS raises the invoice
 * immediately and records the request against it, so the operator has a queue
 * of what was bought for which number instead of a free-text invoice note.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('recharge_requests')) {
            return;
        }

        Schema::create('recharge_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->index();
            $table->decimal('amount', 12, 2);
            $table->string('mobile_number', 190);
            $table->unsignedBigInteger('invoice_id')->nullable()->index();
            // Pending | Completed | Cancelled - the legacy queue's three states.
            $table->string('status', 20)->default('Pending');
            $table->timestamp('requested_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recharge_requests');
    }
};
