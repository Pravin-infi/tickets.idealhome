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
        Schema::create('failed_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('orderId');
            $table->integer('customer_id');
            $table->boolean('success')->default(0);
            $table->mediumText('pre_payment')->nullable();
            $table->mediumText('booking')->nullable();
            $table->mediumText('payment_method')->nullable();
            $table->mediumText('selected_attendees')->nullable();
            $table->mediumText('seats')->nullable();
            $table->mediumText('commission')->nullable();
            $table->string('payment_gateway')->nullable();
            $table->boolean('failed')->default(0);
            $table->string('razorpay_order_id')->nullable();
            $table->mediumText('razorpay_data')->nullable();
            $table->boolean('payment_failed')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('failed_bookings');
    }
};
