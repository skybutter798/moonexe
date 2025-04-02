<?php

// database/migrations/xxxx_xx_xx_add_end_time_to_pairs_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEndTimeToPairsTable extends Migration
{
    public function up()
    {
        Schema::table('pairs', function (Blueprint $table) {
            $table->unsignedTinyInteger('end_time')->after('gate_time'); // values 1-6
        });
    }

    public function down()
    {
        Schema::table('pairs', function (Blueprint $table) {
            $table->dropColumn('end_time');
        });
    }
}