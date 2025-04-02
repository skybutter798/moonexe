<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMatchingrangesTable extends Migration
{
    public function up()
    {
        Schema::create('matchingranges', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('min', 15, 4);
            $table->decimal('max', 15, 4);
            $table->decimal('percentage', 5, 2);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('matchingranges');
    }
}
