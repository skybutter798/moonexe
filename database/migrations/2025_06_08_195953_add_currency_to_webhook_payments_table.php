<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('webhook_payments', function (Blueprint $table) {
            $table->string('currency', 10)->default('USD')->after('status');
        });
    }
    
    public function down()
    {
        Schema::table('webhook_payments', function (Blueprint $table) {
            $table->dropColumn('currency');
        });
    }

};
