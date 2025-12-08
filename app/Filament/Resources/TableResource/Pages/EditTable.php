<?php

namespace App\Filament\Resources\TableResource\Pages;

use App\Filament\Resources\TableResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditTable extends EditRecord
{
    protected static string $resource = TableResource::class;

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
        $url = $this->record->qr_url;
        
        return Notification::make()
            ->success()
            ->title('Table Updated Successfully! ✅')
            ->body("Table **{$this->record->name}** berhasil diupdate!\n\n📱 QR Code URL: {$url}")
            ->duration(3000);
    }
}
