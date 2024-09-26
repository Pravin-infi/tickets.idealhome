<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('city')->nullable();
            $table->string('pincode')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('stripe_account_id')->nullable()->change();
            $table->text('seller_name')->nullable()->change();
            $table->text('seller_info')->nullable()->change();
            $table->text('seller_tax_info')->nullable()->change();
            $table->text('seller_signature')->nullable()->change();
            $table->text('seller_note')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'city',
                'pincode',
                'ip_address'
            ]);
        });
    }
};
