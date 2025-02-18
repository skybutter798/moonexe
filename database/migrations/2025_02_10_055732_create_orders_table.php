<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('pair_id');
            // For a buy order: 'buy' stores the USD spent.
            // For a sell order: 'sell' stores the asset (base currency) sold.
            // 'receive' stores the amount received in the transaction.
            $table->decimal('buy', 15, 8)->nullable();
            $table->decimal('sell', 15, 8)->nullable();
            $table->decimal('receive', 15, 8)->nullable();
            $table->string('status')->default('pending'); // e.g. pending, completed, cancelled
            $table->timestamps();

        });
    }

    public function down()
    {
        Schema::dropIfExists('orders');
    }
}
