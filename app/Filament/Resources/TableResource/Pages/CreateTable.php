<?php

namespace App\Filament\Resources\TableResource\Pages;

use App\Filament\Resources\TableResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateTable extends CreateRecord
{
    protected static string $resource = TableResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Table Created Successfully! 🎉')
            ->body("Table **{$this->record->name}** berhasil dibuat!\n\n📱 QR Code akan otomatis di-generate dalam beberapa detik.\n🔗 URL Order: " . url("/order/{$this->record->name}"))
            ->duration(5000)
            ->actions([
                \Filament\Notifications\Actions\Action::make('viewPrint')
                    ->label('🖨️ Print QR Code')
                    ->url(route('table.print-qr', $this->record->id))
                    ->openUrlInNewTab(),
            ]);
    }
}
