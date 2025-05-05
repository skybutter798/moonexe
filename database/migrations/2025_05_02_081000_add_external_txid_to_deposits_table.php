<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('deposits', function (Blueprint $table) {
            $table->string('external_txid')->nullable()->after('txid');
            $table->unique('external_txid');
        });
    }
    
    public function down()
    {
        Schema::table('deposits', function (Blueprint $table) {
            $table->dropUnique(['external_txid']);
            $table->dropColumn('external_txid');
        });
    }

};
