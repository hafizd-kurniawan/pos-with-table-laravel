<?php

namespace App\Filament\Resources\MyLeavesResource\Pages;

use App\Filament\Resources\MyLeavesResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMyLeaves extends EditRecord
{
    protected static string $resource = MyLeavesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
