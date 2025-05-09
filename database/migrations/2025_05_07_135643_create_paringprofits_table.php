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
        Schema::create('paringprofits', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('min', 15, 4);
            $table->decimal('max', 15, 4);
            $table->unsignedInteger('percentage'); // store 50, 45, etc.
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('paringprofits');
    }
};
