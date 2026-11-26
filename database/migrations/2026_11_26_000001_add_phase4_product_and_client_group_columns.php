<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'trial_days')) {
                $table->unsignedSmallInteger('trial_days')->default(0)->after('terminate_after_days');
            }
            if (! Schema::hasColumn('products', 'compare_price')) {
                $table->decimal('compare_price', 10, 2)->nullable()->after('trial_days');
            }
        });

        Schema::table('client_groups', function (Blueprint $table) {
            if (! Schema::hasColumn('client_groups', 'separate_invoices')) {
                $table->boolean('separate_invoices')->default(false)->after('terminate_exempt');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $drop = array_values(array_filter(
                ['trial_days', 'compare_price'],
                fn (string $col) => Schema::hasColumn('products', $col)
            ));
            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });

        Schema::table('client_groups', function (Blueprint $table) {
            if (Schema::hasColumn('client_groups', 'separate_invoices')) {
                $table->dropColumn('separate_invoices');
            }
        });
    }
};
