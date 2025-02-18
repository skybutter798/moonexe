<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssetsTable extends Migration
{
    public function up()
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            // Currency code or name, e.g. 'MYR'
            $table->string('currency');
            $table->decimal('amount', 15, 8)->default(0);
            $table->string('status')->default('active'); // You might use active/inactive etc.
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('assets');
    }
}
