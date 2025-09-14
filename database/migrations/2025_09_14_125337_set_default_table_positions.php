<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Table;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Set default positions for existing tables in a grid layout
        $tables = Table::whereNull('x_position')->orWhereNull('y_position')->get();
        
        $gridSize = 150; // Jarak antar table (pixel)
        $tablesPerRow = 4; // Jumlah table per baris
        $startX = 50; // Posisi X awal
        $startY = 50; // Posisi Y awal
        
        foreach ($tables as $index => $table) {
            $row = intval($index / $tablesPerRow);
            $col = $index % $tablesPerRow;
            
            $x = $startX + ($col * $gridSize);
            $y = $startY + ($row * $gridSize);
            
            $table->update([
                'x_position' => $x,
                'y_position' => $y
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reset all positions to null
        Table::query()->update([
            'x_position' => null,
            'y_position' => null
        ]);
    }
};
