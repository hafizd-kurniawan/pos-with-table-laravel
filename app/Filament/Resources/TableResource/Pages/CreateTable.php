<?php

namespace App\Filament\Resources\TableResource\Pages;

use App\Filament\Resources\TableResource;
use App\Filament\Traits\HasTenantScope;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateTable extends CreateRecord
{
    use HasTenantScope;

    protected static string $resource = TableResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    protected function getCreatedNotification(): ?Notification
    {
        $url = $this->record->qr_url;
        
        return Notification::make()
            ->success()
            ->title('Table Created Successfully! 🎉')
            ->body("Table **{$this->record->name}** berhasil dibuat!\n\n📱 QR Code akan otomatis di-generate dalam beberapa detik.\n🔗 URL Order: {$url}")
            ->duration(5000)
            ->actions([
                \Filament\Notifications\Actions\Action::make('viewPrint')
                    ->label('🖨️ Print QR Code')
                    ->url(route('table.print-qr', $this->record->id))
                    ->openUrlInNewTab(),
            ]);
    }
}
