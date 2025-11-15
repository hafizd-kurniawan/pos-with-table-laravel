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
}

