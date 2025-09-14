<?php

namespace App\Console\Commands;

use App\Models\Table;
use App\Models\TableCategory;
use Illuminate\Console\Command;

class FixTableCategories extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tables:fix-categories';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix tables without category_id';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tablesWithoutCategory = Table::whereNull('category_id')->count();
        $this->info("Found {$tablesWithoutCategory} tables without category_id");

        if ($tablesWithoutCategory === 0) {
            $this->info('All tables already have categories assigned.');
            return;
        }

        $defaultCategory = TableCategory::first();
        if (!$defaultCategory) {
            $this->error('No categories found. Please run the seeder first.');
            return;
        }

        Table::whereNull('category_id')->update([
            'category_id' => $defaultCategory->id
        ]);

        $this->info("Updated {$tablesWithoutCategory} tables with default category: {$defaultCategory->name}");
    }
}
