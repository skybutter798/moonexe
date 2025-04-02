<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdatePairsTableRemoveAndReaddGateTime extends Migration
{
    public function up()
    {
        // First, drop the existing gate_time column.
        Schema::table('pairs', function (Blueprint $table) {
            // Dropping a column directly shouldn't require Doctrine DBAL for MySQL.
            $table->dropColumn('gate_time');
        });

        // Now, re-add the gate_time column as an integer.
        Schema::table('pairs', function (Blueprint $table) {
            $table->integer('gate_time')->after('volume');
            
            // Add the new rate columns.
            $table->decimal('min_rate', 15, 8)->after('pair_id');
            // Assuming 'rate' already exists, we add max_rate after it.
            $table->decimal('max_rate', 15, 8)->after('rate');
        });
    }

    public function down()
    {
        // Reverse the changes:
        Schema::table('pairs', function (Blueprint $table) {
            // Drop the newly added columns.
            $table->dropColumn(['min_rate', 'max_rate', 'gate_time']);
        });
        
        // Optionally, re-add the original gate_time column as a timestamp or datetime.
        Schema::table('pairs', function (Blueprint $table) {
            $table->timestamp('gate_time')->after('volume')->nullable();
        });
    }
}