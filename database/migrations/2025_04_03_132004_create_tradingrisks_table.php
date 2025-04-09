<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTradingrisksTable extends Migration
{
    public function up()
    {
        Schema::create('tradingrisks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->decimal('value', 8, 2); // Adjust precision/scale as needed
            $table->timestamps();

            // Optional: add a foreign key if you have a users table
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('tradingrisks');
    }
}
