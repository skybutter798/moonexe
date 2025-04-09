<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMatchingrecordsTable extends Migration
{
    public function up()
    {
        Schema::create('matchingrecords', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('referral_group');
            $table->decimal('total_contribute', 15, 4)->default(0);
            $table->decimal('total_trade', 15, 4)->default(0);
            // New columns added:
            $table->decimal('total_ref_contribute', 15, 4)->default(0);
            $table->decimal('total_deposit', 15, 4)->default(0);
            $table->decimal('ref_balance', 15, 4)->default(0);
            $table->decimal('matching_balance', 15, 4)->default(0);
            $table->date('record_date');
            $table->timestamps();

            // Optional: add indexes for faster queries
            $table->index(['user_id', 'record_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('matchingrecords');
    }
}
