<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Filament\Traits\HasTenantScope;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    use HasTenantScope;
    
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            /* Actions\DeleteAction::make(), */
        ];
    }

    protected function getRedirectUrl(): string
    {
        return ProductResource::getUrl('index');
    }
}
