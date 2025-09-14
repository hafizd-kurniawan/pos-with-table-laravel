<?php

namespace Database\Seeders;

use App\Models\Table;
use Illuminate\Database\Seeder;

class UpdateTableQRCodesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Update all existing tables with QR codes
        $tables = Table::whereNull('qr_code')->orWhere('qr_code', '')->get();
        
        foreach ($tables as $table) {
            $url = url("/order/{$table->name}");
            $table->update(['qr_code' => $url]);
            
            $this->command->info("Generated QR code for table: {$table->name} -> {$url}");
        }
        
        $this->command->info("QR codes generated for " . $tables->count() . " tables.");
    }
}