<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('user_range_overrides', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique(); // one override per user; drop unique if you want multiple windows
            // You can lock to a particular row in directranges/matchingranges...
            $table->unsignedBigInteger('direct_range_id')->nullable();
            $table->unsignedBigInteger('matching_range_id')->nullable();
            // ...or bypass ranges entirely with a raw percentage override:
            $table->decimal('direct_percentage_override', 6, 3)->nullable();
            $table->decimal('matching_percentage_override', 6, 3)->nullable();

            // Optional validity window; if null, always active
            $table->timestamp('effective_from')->nullable();
            $table->timestamp('effective_to')->nullable();

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            // If you want FKs to the ranges tables (recommended if stable):
            // $table->foreign('direct_range_id')->references('id')->on('directranges')->nullOnDelete();
            // $table->foreign('matching_range_id')->references('id')->on('matchingranges')->nullOnDelete();
        });
    }

    public function down(): void {
        Schema::dropIfExists('user_range_overrides');
    }
};