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
        Schema::create('webhook_payments', function (Blueprint $table) {
            $table->id();
            $table->string('pay_id')->unique();
            $table->string('method')->nullable();
            $table->decimal('amount', 20, 10)->default(0);
            $table->string('status');
            $table->timestamps(); // includes created_at and updated_at
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('webhook_payments');
    }
};
