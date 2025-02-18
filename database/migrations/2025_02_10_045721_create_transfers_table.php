<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransfersTable extends Migration
{
    public function up()
    {
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');  // (No foreign key constraint)
            $table->string('txid')->unique();
            $table->string('from_wallet');
            $table->string('to_wallet');
            $table->decimal('amount', 15, 2);
            $table->string('status')->default('Completed');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('transfers');
    }
}
