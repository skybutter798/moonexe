<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStakingsTable extends Migration
{
    public function up()
    {
        Schema::create('stakings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('txid')->unique();
            $table->decimal('amount', 18, 8);
            $table->decimal('interest', 18, 8)->default(0);
            $table->decimal('balance', 18, 8)->default(0);
            $table->string('status')->default('pending'); // you can change this to enum if needed
            $table->timestamps(); // created_at and updated_at
        });
    }

    public function down()
    {
        Schema::dropIfExists('stakings');
    }
}
