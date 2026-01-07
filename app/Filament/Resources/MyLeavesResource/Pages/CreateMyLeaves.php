<?php

namespace App\Filament\Resources\MyLeavesResource\Pages;

use App\Filament\Resources\MyLeavesResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateMyLeaves extends CreateRecord
{
    protected static string $resource = MyLeavesResource::class;
}
