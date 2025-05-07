<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('annoucements', function (Blueprint $table) {
            $table->string('banner_image')->nullable()->after('status');
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('annoucements', function (Blueprint $table) {
            //
        });
    }
};
