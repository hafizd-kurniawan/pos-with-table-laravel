<?php

namespace App\Filament\Resources\SettingResource\Pages;

use App\Filament\Resources\SettingResource;
use App\Models\Setting;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;

class EditSetting extends EditRecord
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
        // Clear all cache when setting is updated
        Setting::clearCache();
        Cache::flush();
        
        return Notification::make()
            ->success()
            ->title('Setting Updated Successfully! ✅')
            ->body("Setting **{$this->record->label}** berhasil diupdate!\n\nPerubahan akan terlihat setelah refresh halaman.")
            ->duration(5000)
            ->actions([
                \Filament\Notifications\Actions\Action::make('refresh')
                    ->label('🔄 Refresh Page')
                    ->action('$wire.emit("refresh-page")')
                    ->close(),
            ]);
    }
}