<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('service_transfer_requests')) {
            Schema::create('service_transfer_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('service_id')->index();
                $table->unsignedBigInteger('from_client_id')->index();
                $table->unsignedBigInteger('to_client_id')->nullable();
                $table->string('to_name', 255);
                $table->string('to_email', 255);
                $table->string('to_phone', 64)->nullable();
                $table->text('reason');
                $table->string('status', 24)->default('pending')->index();
                $table->unsignedBigInteger('processed_by_admin_id')->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->text('admin_note')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('service_transfer_requests');
    }
};
