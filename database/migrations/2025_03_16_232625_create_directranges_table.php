<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDirectrangesTable extends Migration
{
    public function up()
    {
        Schema::create('directranges', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // Using decimal with 4 decimals. Here, 10 total digits (adjust if needed)
            $table->decimal('min', 15, 4);
            $table->decimal('max', 15, 4);
            // Assuming percentage can have 2 decimals (adjust precision/scale as needed)
            $table->decimal('percentage', 5, 2);
            $table->timestamps(); // This creates both created_at and updated_at
        });
    }

    public function down()
    {
        Schema::dropIfExists('directranges');
    }
}
