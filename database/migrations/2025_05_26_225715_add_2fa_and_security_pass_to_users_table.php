<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('two_fa_enabled')->default(false); // for 2FA toggle
            $table->string('security_pass')->nullable();       // for secondary password
        });
    }
    
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['two_fa_enabled', 'security_pass']);
        });
    }

};
