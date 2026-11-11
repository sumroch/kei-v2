<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * email_bcc holds a JSON list of addresses; existing stores already use ~230 of 256 chars.
     */
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->text('email_bcc')->change();
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->string('email_bcc', 256)->change();
        });
    }
};
