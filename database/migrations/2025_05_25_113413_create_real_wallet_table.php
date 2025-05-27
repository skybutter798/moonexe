<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRealWalletTable extends Migration
{
    public function up(): void
    {
        Schema::create('real_wallet', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->decimal('cash_wallet', 20, 8)->default(0);
            $table->decimal('trading_wallet', 20, 8)->default(0);
            $table->decimal('earning_wallet', 20, 8)->default(0);
            $table->decimal('affiliates_wallet', 20, 8)->default(0);
            $table->decimal('bonus_wallet', 20, 8)->default(0);
            $table->text('remark')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('real_wallet');
    }
}
