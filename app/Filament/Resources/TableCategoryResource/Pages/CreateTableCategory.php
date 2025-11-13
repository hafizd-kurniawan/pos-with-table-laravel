<?php

namespace App\Filament\Resources\TableCategoryResource\Pages;

use App\Filament\Resources\TableCategoryResource;
use App\Filament\Traits\HasTenantScope;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTableCategory extends CreateRecord
{
    use HasTenantScope;

    protected static string $resource = TableCategoryResource::class;
}
