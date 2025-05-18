<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('deposits', function (Blueprint $table) {
            $table->decimal('fee', 16, 2)->default(0)->after('amount');
        });
    }
    
    public function down()
    {
        Schema::table('deposits', function (Blueprint $table) {
            $table->dropColumn('fee');
        });
    }

};
