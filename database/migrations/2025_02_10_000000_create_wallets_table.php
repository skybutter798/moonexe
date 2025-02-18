<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWalletsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();

            // The wallet is associated with a unique user.
            $table->unsignedBigInteger('user_id')->unique();

            // Define the wallet columns with default values
            $table->decimal('cash_wallet', 10, 2)->default(0.00);
            $table->decimal('trading_wallet', 10, 2)->default(0.00);
            $table->decimal('earning_wallet', 10, 2)->default(0.00);
            $table->decimal('affiliates_wallet', 10, 2)->default(0.00);

            // Status flag: 1 = active, 0 = inactive
            $table->boolean('status')->default(1);

            // Laravel timestamps (created_at and updated_at)
            $table->timestamps();

            // Removed the foreign key constraint as it's not needed.
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wallets');
    }
}
