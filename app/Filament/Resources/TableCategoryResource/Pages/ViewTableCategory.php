<?php

namespace App\Filament\Resources\TableCategoryResource\Pages;

use App\Filament\Resources\TableCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTableCategory extends ViewRecord
{
    protected static string $resource = TableCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
