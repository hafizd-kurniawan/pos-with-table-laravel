<?php

namespace App\Filament\Resources\IngredientCategoryResource\Pages;

use App\Filament\Resources\IngredientCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateIngredientCategory extends CreateRecord
{
    protected static string $resource = IngredientCategoryResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // ✅ Auto-fill tenant_id dari user yang login
        $data['tenant_id'] = auth()->user()->tenant_id;
        
        return $data;
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
