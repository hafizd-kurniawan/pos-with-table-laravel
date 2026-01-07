<?php

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Filament\Resources\PurchaseOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchaseOrder extends CreateRecord
{
    protected static string $resource = PurchaseOrderResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id'] = auth()->user()->tenant_id;
        $data['po_number'] = \App\Models\PurchaseOrder::generatePONumber(auth()->user()->tenant_id);
        $data['created_by'] = auth()->id();
        
        return $data;
    }
    
    protected function afterCreate(): void
    {
        // Calculate totals after items are saved
        $this->record->calculateTotals();
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
