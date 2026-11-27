<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->unsignedBigInteger('client_id')->nullable()->change();
            $table->text('proposal')->nullable()->change();

            $table->string('first_name')->nullable()->after('client_id');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('company_name')->nullable()->after('last_name');
            $table->string('email')->nullable()->after('company_name');
            $table->string('address1')->nullable()->after('email');
            $table->string('address2')->nullable()->after('address1');
            $table->string('city')->nullable()->after('address2');
            $table->string('state')->nullable()->after('city');
            $table->string('postcode', 30)->nullable()->after('state');
            $table->string('country', 2)->nullable()->after('postcode');
            $table->string('phone_prefix', 10)->nullable()->after('country');
            $table->string('phone_number', 40)->nullable()->after('phone_prefix');
            $table->unsignedBigInteger('currency_id')->nullable()->after('phone_number');
        });

        Schema::table('quote_items', function (Blueprint $table) {
            $table->decimal('quantity', 10, 2)->default(1)->change();
            $table->decimal('discount_percent', 8, 2)->default(0)->after('discount');
        });
    }

    public function down(): void
    {
        Schema::table('quote_items', function (Blueprint $table) {
            $table->dropColumn('discount_percent');
            $table->integer('quantity')->default(1)->change();
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn([
                'first_name',
                'last_name',
                'company_name',
                'email',
                'address1',
                'address2',
                'city',
                'state',
                'postcode',
                'country',
                'phone_prefix',
                'phone_number',
                'currency_id',
            ]);
            $table->string('proposal')->nullable()->change();
        });
    }
};
