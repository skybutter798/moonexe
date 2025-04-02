<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payouts', function (Blueprint $table) {
            $table->string('txid')->unique()->after('order_id')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('payouts', function (Blueprint $table) {
            $table->dropColumn('txid');
        });
    }

};
