<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('scrolling_notices')) {
            Schema::create('scrolling_notices', function (Blueprint $table) {
                $table->id();
                $table->string('title', 255)->nullable();
                $table->text('content');
                $table->integer('priority')->default(0);
                $table->string('url_target', 512)->nullable();
                $table->dateTime('start_time')->nullable();
                $table->dateTime('expiry_time')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->string('login_status', 20)->default('all');
                $table->boolean('coupon_enabled')->default(false);
                $table->string('coupon_code', 64)->nullable();
                $table->unsignedSmallInteger('scroll_speed')->default(5);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('scrolling_notices');
    }
};
