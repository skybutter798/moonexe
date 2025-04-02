<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RangesSeeder extends Seeder
{
    public function run()
    {
        $now = Carbon::now();

        // Direct ranges
        DB::table('directranges')->insert([
            ['name' => 'Direct Range 1', 'min' => 100, 'max' => 999, 'percentage' => 0.03, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Direct Range 2', 'min' => 1000, 'max' => 9999, 'percentage' => 0.05, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Direct Range 3', 'min' => 10000, 'max' => 99999, 'percentage' => 0.08, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Direct Range 4', 'min' => 100000, 'max' => 999999, 'percentage' => 0.10, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Direct Range 5', 'min' => 1000000, 'max' => 4999999, 'percentage' => 0.12, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Direct Range 6', 'min' => 5000000, 'max' => 9999999, 'percentage' => 0.15, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Direct Range 7', 'min' => 10000000, 'max' => 100000000000, 'percentage' => 0.18, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // Matching ranges
        DB::table('matchingranges')->insert([
            ['name' => 'Matching Range 1', 'min' => 100, 'max' => 999, 'percentage' => 0.05, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Matching Range 2', 'min' => 1000, 'max' => 9999, 'percentage' => 0.15, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Matching Range 3', 'min' => 10000, 'max' => 99999, 'percentage' => 0.25, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Matching Range 4', 'min' => 100000, 'max' => 999999, 'percentage' => 0.30, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Matching Range 5', 'min' => 1000000, 'max' => 4999999, 'percentage' => 0.40, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Matching Range 6', 'min' => 5000000, 'max' => 9999999, 'percentage' => 0.50, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Matching Range 7', 'min' => 10000000, 'max' => 100000000000, 'percentage' => 0.60, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}
