<?php

namespace App\Filament\Resources\CashierSessionResource\Pages;

use App\Filament\Resources\CashierSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCashierSession extends ViewRecord
{
    protected static string $resource = CashierSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
