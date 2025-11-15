<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Main Database Seeder (SaaS Mode)
 * 
 * This seeder is SAFE for multi-tenant SaaS.
 * It only calls SaaS-ready seeders.
 * 
 * For development: php artisan migrate:fresh --seed
 * For production: php artisan db:seed --class=SaaSDatabaseSeeder
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting SaaS Database Seeding...');
        $this->command->newLine();
        
        // Call the main SaaS seeder
        $this->call(SaaSDatabaseSeeder::class);
        
        $this->command->newLine();
        $this->command->info('✅ Database seeding complete!');
    }
}
