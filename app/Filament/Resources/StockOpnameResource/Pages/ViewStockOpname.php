<?php

namespace App\Filament\Resources\StockOpnameResource\Pages;

use App\Filament\Resources\StockOpnameResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewStockOpname extends ViewRecord
{
    protected static string $resource = StockOpnameResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn ($record) => $record->status === 'draft'),
            
            Actions\Action::make('complete')
                ->label('Complete Opname')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn ($record) => $record->status === 'draft' && $record->items()->count() > 0)
                ->requiresConfirmation()
                ->modalHeading('Complete Stock Opname')
                ->modalDescription(function ($record) {
                    $summary = $record->differences_summary;
                    return "This will adjust stock for all items with differences. " .
                           "{$summary['items_with_difference']} items will be adjusted out of {$summary['total_items']} total items.";
                })
                ->action(function ($record) {
                    $record->complete();
                    
                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title('Stock Opname Completed!')
                        ->body('Stock has been adjusted for all items with differences.')
                        ->send();
                    
                    return redirect()->to(StockOpnameResource::getUrl('view', ['record' => $record]));
                }),
        ];
    }
}
