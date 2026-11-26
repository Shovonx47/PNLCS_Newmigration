<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3 - SMS.
 *
 * One settings row (as the legacy addon had), the operator's templates, a log
 * of every message that was attempted, and the registration OTPs.
 *
 * The legacy install kept the API secret in a plain varchar; here it is
 * encrypted by the model's cast. Nothing else is copied: there is no session
 * id column, because an OTP belongs to a phone number and an expiry, not to a
 * PHP session that dies when the browser closes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('softcents_sms_settings', function (Blueprint $table) {
            $table->id();
            $table->string('client_id', 100)->nullable();
            $table->text('client_secret')->nullable();
            $table->string('sender_id', 50)->nullable();
            $table->string('date_format', 20)->default('d-m-Y');

            $table->boolean('otp_enabled')->default(false);
            $table->boolean('otp_checkout_enabled')->default(false);
            $table->unsignedTinyInteger('otp_length')->default(6);
            $table->unsignedSmallInteger('otp_expiry_minutes')->default(5);
            $table->unsignedSmallInteger('otp_resend_cooldown')->default(60);
            $table->unsignedSmallInteger('otp_max_per_hour')->default(5);
            $table->text('otp_template')->nullable();

            $table->text('admin_numbers')->nullable();
            $table->boolean('admin_alerts')->default(false);
            $table->timestamps();
        });

        Schema::create('softcents_sms_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('type', 10)->default('client');
            $table->text('template')->nullable();
            $table->string('variables', 500)->nullable();
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('softcents_sms_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('client_id')->nullable()->index();
            $table->string('to_number', 30);
            $table->string('template_name', 80)->nullable();
            $table->text('message')->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('error', 500)->nullable();
            $table->unsignedSmallInteger('http_code')->nullable();
            $table->timestamps();
        });

        Schema::create('softcents_sms_otps', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20)->index();
            $table->string('otp_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->boolean('verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->boolean('consumed')->default(false);
            $table->string('ip_address', 45)->nullable();
            $table->dateTime('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('softcents_sms_otps');
        Schema::dropIfExists('softcents_sms_logs');
        Schema::dropIfExists('softcents_sms_templates');
        Schema::dropIfExists('softcents_sms_settings');
    }
};
