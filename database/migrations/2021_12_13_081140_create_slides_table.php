<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSlidesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('slides', function (Blueprint $table) {
            $table->id();
            $table->string('owl', 64)->nullable();
            $table->string('color', 32)->nullable();
            $table->string('title1', 64)->nullable();
            $table->string('title2', 64)->nullable();
            $table->string('maker', 32)->nullable();
            $table->string('dots', 32)->nullable();
            // Matches the real ichiman schema and SlideController (orderBy order_slide, active = 1/0).
            $table->tinyInteger('order_slide')->nullable()->default(9);
            $table->boolean('active')->default(true);
            $table->string('store_type', 32);
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
        Schema::dropIfExists('slides');
    }
}
