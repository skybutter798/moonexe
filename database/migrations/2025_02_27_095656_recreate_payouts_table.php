<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RecreatePayoutsTable extends Migration
{
    public function up()
    {
        // If the table already exists, drop it.
        Schema::dropIfExists('payouts');
        
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->decimal('total', 16, 2);
            $table->decimal('actual', 16, 2);
            $table->string('type'); // You can adjust the data type as needed.
            $table->decimal('wallet', 16, 2);
            $table->unsignedTinyInteger('status')->default(1);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('payouts');
    }
}