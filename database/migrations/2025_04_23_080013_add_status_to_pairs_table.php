<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusToPairsTable extends Migration
{
    public function up()
    {
        Schema::table('pairs', function (Blueprint $table) {
            $table->tinyInteger('status')
                  ->default(1)
                  ->after('end_time');
        });
    }

    public function down()
    {
        Schema::table('pairs', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
}
