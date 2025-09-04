<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('annoucements', function (Blueprint $table) {
            $table->boolean('show')->default(1)->after('status'); // replace 'your_column' with the column before 'show'
        });
    }
    
    public function down()
    {
        Schema::table('annoucements', function (Blueprint $table) {
            $table->dropColumn('show');
        });
    }

};
