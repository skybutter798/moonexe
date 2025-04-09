<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRecordDateToAssetsrecordsTable extends Migration
{
    public function up()
    {
        Schema::table('assetsrecords', function (Blueprint $table) {
            $table->date('record_date')->nullable()->after('value');
        });
    }

    public function down()
    {
        Schema::table('assetsrecords', function (Blueprint $table) {
            $table->dropColumn('record_date');
        });
    }
}
