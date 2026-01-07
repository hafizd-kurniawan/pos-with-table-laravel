<?php

namespace App\Filament\Resources\StockOpnameResource\Pages;

use App\Filament\Resources\StockOpnameResource;
use App\Models\StockOpname;
use Filament\Resources\Pages\CreateRecord;

class CreateStockOpname extends CreateRecord
{
    protected static string $resource = StockOpnameResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Auto-fill tenant_id from logged-in user
        $data['tenant_id'] = auth()->user()->tenant_id;
        
        // Auto-fill user_id
        $data['user_id'] = auth()->id();
        
        // Auto-generate opname number
        $data['opname_number'] = StockOpname::generateOpnameNumber(auth()->user()->tenant_id);
        
        // Ensure status is draft
        $data['status'] = 'draft';
        
        return $data;
    }
    
    protected function afterCreate(): void
    {
        // Calculate and save differences for items
        foreach ($this->record->items as $item) {
            $item->update([
                'difference' => $item->physical_qty - $item->system_qty,
            ]);
        }
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
