<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\Role;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Auto-assign current tenant_id
        $data['tenant_id'] = auth()->user()->tenant_id;
        
        // If no role specified, assign default role
        if (empty($data['role_id'])) {
            $defaultRole = Role::getDefault(auth()->user()->tenant_id);
            if ($defaultRole) {
                $data['role_id'] = $defaultRole->id;
            }
        }
        
        return $data;
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
