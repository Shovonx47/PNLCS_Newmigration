<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('acv_verifications')) {
            Schema::create('acv_verifications', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_id')->unique();
                $table->string('session_id', 128)->nullable()->index();
                $table->text('verification_url')->nullable();
                $table->string('status', 32)->default('not_started')->index();
                $table->string('verification_method', 20)->default('api');
                $table->string('document_type', 64)->nullable();
                $table->string('document_number', 128)->nullable();
                $table->string('business_type', 64)->nullable();
                $table->string('trade_license_number', 128)->nullable();
                $table->text('decline_reason')->nullable();
                $table->longText('webhook_payload')->nullable();
                $table->json('acv_suspended_service_ids')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->unsignedBigInteger('verified_by_admin_id')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('acv_documents')) {
            Schema::create('acv_documents', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('verification_id')->index();
                $table->unsignedBigInteger('client_id')->index();
                $table->string('file_type', 32);
                $table->string('original_name', 255);
                $table->string('storage_path', 512);
                $table->unsignedInteger('file_size')->default(0);
                $table->string('mime_type', 100);
                $table->string('status', 20)->default('pending');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('acv_admin_notes')) {
            Schema::create('acv_admin_notes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('verification_id')->index();
                $table->unsignedBigInteger('admin_id')->nullable();
                $table->string('admin_name', 120)->nullable();
                $table->text('note');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('acv_activity_logs')) {
            Schema::create('acv_activity_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_id')->index();
                $table->string('action', 64);
                $table->text('description')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->unsignedBigInteger('admin_id')->nullable();
                $table->string('admin_name', 120)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('acv_reminders')) {
            Schema::create('acv_reminders', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_id');
                $table->date('sent_on');
                $table->timestamps();
                $table->unique(['client_id', 'sent_on']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('acv_reminders');
        Schema::dropIfExists('acv_activity_logs');
        Schema::dropIfExists('acv_admin_notes');
        Schema::dropIfExists('acv_documents');
        Schema::dropIfExists('acv_verifications');
    }
};
