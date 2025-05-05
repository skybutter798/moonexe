<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('wallet_qr')->nullable()->after('wallet_address');
            $table->timestamp('wallet_expired')->nullable()->after('wallet_qr');
        });
    }
    
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['wallet_qr', 'wallet_expired']);
        });
    }

};
