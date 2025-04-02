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
    Schema::create('market_data', function (Blueprint $table) {
        $table->id();
        $table->string('symbol')->unique();
        $table->decimal('bid', 10, 4);
        $table->decimal('ask', 10, 4);
        $table->decimal('mid', 10, 4);
        $table->timestamps();
    });
}

public function down()
{
    Schema::dropIfExists('market_data');
}
};
