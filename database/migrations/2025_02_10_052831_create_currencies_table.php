<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCurrenciesTable extends Migration
{
    public function up()
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('c_name');
            $table->tinyInteger('status')->default(1);
            $table->timestamps(); // This creates both 'created_at' and 'updated_at'
        });
    }

    public function down()
    {
        Schema::dropIfExists('currencies');
    }
}
