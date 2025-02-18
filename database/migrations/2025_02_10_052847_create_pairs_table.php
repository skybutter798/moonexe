<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePairsTable extends Migration
{
    public function up()
    {
        Schema::create('pairs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('currency_id');
            $table->unsignedBigInteger('pair_id');
            $table->decimal('rate', 15, 8);   // Adjust precision and scale as needed
            $table->decimal('volume', 15, 8); // Adjust precision and scale as needed
            $table->timestamp('gate_time')->nullable();
            $table->timestamps(); // This creates 'created_at' and 'updated_at'
            
            // Optionally add foreign keys:
            $table->foreign('currency_id')->references('id')->on('currencies')->onDelete('cascade');
            $table->foreign('pair_id')->references('id')->on('currencies')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('pairs', function (Blueprint $table) {
            $table->dropForeign(['currency_id']);
            $table->dropForeign(['pair_id']);
        });
        Schema::dropIfExists('pairs');
    }
}
