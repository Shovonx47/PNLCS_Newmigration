<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cancel_invoice_exclusions')) {
            Schema::create('cancel_invoice_exclusions', function (Blueprint $table) {
                $table->id();
                $table->string('type', 20);
                $table->unsignedBigInteger('rel_id');
                $table->string('note', 255)->nullable();
                $table->timestamps();
                $table->unique(['type', 'rel_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cancel_invoice_exclusions');
    }
};
