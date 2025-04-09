<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRecordchecksTable extends Migration
{
    public function up()
    {
        Schema::create('recordchecks', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key
            $table->string('name');
            $table->timestamp('time')->nullable(); // Optional timestamp column for 'time'
            // If you want Laravel to manage both created_at and updated_at columns, you can use:
            // $table->timestamps();
            // But here, since you only mentioned created_at, you can add it manually:
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down()
    {
        Schema::dropIfExists('recordchecks');
    }
}
