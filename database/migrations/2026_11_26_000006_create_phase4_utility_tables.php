<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('price_updater_logs')) {
            Schema::create('price_updater_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('admin_id')->nullable();
                $table->string('target_type', 24);
                $table->string('mode', 16);
                $table->decimal('delta', 10, 2);
                $table->json('filters')->nullable();
                $table->unsignedInteger('affected_count')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('gdrive_backup_logs')) {
            Schema::create('gdrive_backup_logs', function (Blueprint $table) {
                $table->id();
                $table->string('type', 24);
                $table->string('status', 24);
                $table->string('file_name', 255)->nullable();
                $table->string('drive_file_id', 128)->nullable();
                $table->unsignedBigInteger('size_bytes')->default(0);
                $table->text('message')->nullable();
                $table->string('triggered_by', 120)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('support_pins')) {
            Schema::create('support_pins', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_id')->unique();
                $table->string('pin_hash', 255);
                $table->text('pin_encrypted');
                $table->timestamp('expires_at');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('support_pin_logs')) {
            Schema::create('support_pin_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_id')->nullable()->index();
                $table->unsignedBigInteger('admin_id')->nullable();
                $table->string('result', 32);
                $table->string('ip_address', 45)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('forced_active_clients')) {
            Schema::create('forced_active_clients', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_id')->unique();
                $table->unsignedBigInteger('added_by_admin_id')->nullable();
                $table->string('note', 255)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('forced_active_logs')) {
            Schema::create('forced_active_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_id')->index();
                $table->string('previous_status', 32);
                $table->timestamp('restored_at');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('ip_unblock_logs')) {
            Schema::create('ip_unblock_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_id')->index();
                $table->unsignedBigInteger('service_id')->index();
                $table->string('ip_address', 45);
                $table->boolean('success')->default(false);
                $table->string('message', 255)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ip_unblock_logs');
        Schema::dropIfExists('forced_active_logs');
        Schema::dropIfExists('forced_active_clients');
        Schema::dropIfExists('support_pin_logs');
        Schema::dropIfExists('support_pins');
        Schema::dropIfExists('gdrive_backup_logs');
        Schema::dropIfExists('price_updater_logs');
    }
};
