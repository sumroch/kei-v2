<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCarsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cars', function (Blueprint $table) {
            $table->id();
            $table->string('brand', 128);
            $table->string('maker', 128);
            $table->string('picture', 256);
            $table->string('fuel', 128)->nullable();
            $table->string('grade', 128)->nullable();
            $table->boolean('new')->default(true);
            $table->string('price', 32)->default(0);
            $table->string('total', 32)->default(0);
            $table->string('monthly', 32)->default(0);
            $table->string('store_type', 64);
            $table->boolean('active')->default(true);
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
        Schema::dropIfExists('cars');
    }
}
