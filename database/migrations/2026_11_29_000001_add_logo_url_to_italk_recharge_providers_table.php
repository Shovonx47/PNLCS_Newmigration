<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('italk_recharge_providers') && ! Schema::hasColumn('italk_recharge_providers', 'logo_url')) {
            Schema::table('italk_recharge_providers', function (Blueprint $table) {
                $table->string('logo_url', 255)->nullable()->after('api_key');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('italk_recharge_providers') && Schema::hasColumn('italk_recharge_providers', 'logo_url')) {
            Schema::table('italk_recharge_providers', function (Blueprint $table) {
                $table->dropColumn('logo_url');
            });
        }
    }
};
