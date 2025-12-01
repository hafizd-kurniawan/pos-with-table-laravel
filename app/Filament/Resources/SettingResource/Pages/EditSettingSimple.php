<?php

namespace App\Filament\Resources\SettingResource\Pages;

use App\Filament\Resources\SettingResource;
use App\Models\Setting;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Filament\Forms;
use Illuminate\Database\Eloquent\Model;

/**
 * ULTRA SIMPLE EDIT - JUST USE RESOURCE FORM, NO TRICKS!
 */
class EditSettingSimple extends EditRecord
{
    protected static string $resource = SettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    protected function getSavedNotification(): ?Notification
    {
        Setting::clearCache();
        Cache::forget('settings.all');
        
        return Notification::make()
            ->success()
            ->title('Setting Updated! ✅')
            ->body("Setting berhasil diupdate!")
            ->duration(3000);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['value_text'] = $data['value'];
        $data['value_boolean'] = $data['value'];
        $data['value_color'] = $data['value'];
        $data['value_file'] = $data['value'];
        
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
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

