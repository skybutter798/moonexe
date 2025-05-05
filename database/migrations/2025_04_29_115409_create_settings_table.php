<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id(); // id (auto increment)
            $table->string('name')->unique(); // Setting name
            $table->text('value')->nullable(); // Setting value
            $table->tinyInteger('status')->default(1); // 1 = active, 0 = inactive
            $table->string('remark')->nullable(); // Optional remark
            $table->timestamps(); // created_at and updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
