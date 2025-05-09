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
        $table->unsignedBigInteger('user_id')->nullable()->after('id');
        // Optionally, add a foreign key constraint:
        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
    });
}
public function down()
{
    Schema::table('payouts', function (Blueprint $table) {
        $table->dropForeign(['user_id']);
        $table->dropColumn('user_id');
    });
}
};
