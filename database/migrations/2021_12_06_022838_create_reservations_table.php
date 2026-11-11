<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReservationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('name', 128)->nullable();
            $table->string('how_to_read', 128)->nullable();
            $table->string('phone_number', 32)->nullable();
            $table->string('email', 128)->nullable();
            $table->string('time1', 64)->nullable();
            $table->string('time2', 16)->nullable();
            $table->string('time3', 16)->nullable();
            $table->string('date1', 64)->nullable();
            $table->string('date2', 32)->nullable();
            $table->string('date3', 32)->nullable();
            $table->string('can_contacted', 256)->nullable();
            $table->string('can_contacted_day', 256)->nullable();
            $table->string('address', 128)->nullable();
            $table->string('question', 2048)->nullable();
            $table->string('other_question', 2048)->nullable();
            $table->string('message', 2048)->nullable();
            $table->string('type_reservation', 32)->nullable();
            $table->string('type_question', 512)->nullable();
            $table->string('type_customer', 32)->nullable();
            $table->string('usage', 32)->nullable();
            $table->string('buyer', 32)->nullable();
            $table->string('car', 2048)->nullable();
            $table->string('other_car', 128)->nullable();
            // Customer data: block hard-deleting a store that still has reservations instead of wiping them.
            $table->foreignId('store_id')->constrained('stores')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reservations');
    }
}
