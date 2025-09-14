<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $tables = [
            ['number' => 1, 'capacity' => 4],
            ['number' => 2, 'capacity' => 4],
            ['number' => 3, 'capacity' => 2],
            ['number' => 4, 'capacity' => 6],
            ['number' => 5, 'capacity' => 4],
            ['number' => 6, 'capacity' => 2],
            ['number' => 7, 'capacity' => 4],
            ['number' => 8, 'capacity' => 6],
            ['number' => 9, 'capacity' => 4],
            ['number' => 10, 'capacity' => 2],
        ];

        foreach ($tables as $table) {
            \App\Models\Table::create([
                'name' => $table['number'],
                'capacity' => $table['capacity'],
                'status' => 'available',
                'qr_code' => null, // Assuming QR code generation is handled elsewhere
            ]);
        }
    }
}
