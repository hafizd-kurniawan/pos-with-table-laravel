<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Auto-assign current tenant_id
        $data['tenant_id'] = auth()->user()->tenant_id;
        $data['is_system'] = false; // Custom roles are not system roles
        
        return $data;
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
