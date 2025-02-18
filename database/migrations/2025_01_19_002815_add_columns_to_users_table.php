<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_login')->nullable()->after('remember_token');
            $table->string('role')->default('user')->after('last_login'); // Default role as 'user'
            $table->unsignedBigInteger('referral')->nullable()->after('role'); // Refers to another user's ID
            $table->string('referral_code')->unique()->nullable()->after('referral');
            $table->string('referral_link')->nullable()->after('referral_code'); // A generated link
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['last_login', 'role', 'referral', 'referral_code', 'referral_link']);
        });
    }
}
