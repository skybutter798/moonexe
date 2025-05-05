<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAnnoucementsTable extends Migration
{
    public function up()
    {
        Schema::create('annoucements', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('content');
            $table->boolean('status')->default(false);
            $table->timestamps(); // created_at + updated_at
        });
    }

    public function down()
    {
        Schema::dropIfExists('annoucements');
    }
}
