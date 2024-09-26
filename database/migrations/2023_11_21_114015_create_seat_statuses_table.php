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
        Schema::create('seat_statuses', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('seat_id')->index();
            $table->boolean('is_checked')->default(0);
            $table->json('seats')->nullable();
            $table->date('event_start_date')->nullable();
            $table->foreign('seat_id')->references('id')->on('seats')->onDelete('cascade');
            $table->unique('seat_id', 'event_start_date');
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
        Schema::dropIfExists('seat_statuses');
    }
};
