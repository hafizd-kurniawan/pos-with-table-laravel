<?php

namespace App\Filament\Resources\SettingResource\Pages;

use App\Filament\Resources\SettingResource;
use App\Filament\Traits\HasTenantScope;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateSetting extends CreateRecord
{
    use HasTenantScope;

    protected static string $resource = SettingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $type = $data['type'];
        
        if (in_array($type, ['text', 'textarea', 'email', 'url', 'number'])) {
            $data['value'] = $data['value_text'];
        } elseif ($type === 'boolean') {
            $data['value'] = $data['value_boolean'];
        } elseif ($type === 'color') {
            $data['value'] = $data['value_color'];
        } elseif ($type === 'file') {
            $data['value'] = $data['value_file'];
        }
        
        unset($data['value_text'], $data['value_boolean'], $data['value_color'], $data['value_file']);
        
        return $data;
    }
}
