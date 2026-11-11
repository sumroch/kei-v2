<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGyautoQuestionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gyauto_questions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 128)->nullable();
            $table->string('how_to_read', 128)->nullable();
            $table->string('phone_number', 32)->nullable();
            $table->string('gender', 32)->nullable();
            $table->string('date_birth', 32)->nullable();
            $table->string('email', 128)->nullable();
            $table->string('address', 128)->nullable();
            $table->string('married_status', 32)->nullable();
            $table->string('living_status', 128)->nullable();
            $table->string('living_year', 128)->nullable();
            $table->string('licence_number', 128)->nullable();
            $table->string('car_name', 128)->nullable();
            $table->string('company', 128)->nullable();
            $table->string('company_telephone', 32)->nullable();
            $table->string('company_address', 128)->nullable();
            $table->string('salary', 128)->nullable();
            $table->string('work_year', 16)->nullable();
            $table->string('company_type', 64)->nullable();
            $table->string('job', 64)->nullable();
            $table->string('date_1', 64)->nullable();
            $table->string('date_2', 64)->nullable();
            $table->string('date_3', 64)->nullable();
            $table->string('message', 2048)->nullable();
            $table->string('type_question', 512)->nullable();
            // Customer data: block hard-deleting a store that still has questions instead of wiping them.
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
        Schema::dropIfExists('gyauto_questions');
    }
}
