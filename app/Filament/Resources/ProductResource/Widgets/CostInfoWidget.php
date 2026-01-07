<?php

namespace App\Filament\Resources\ProductResource\Widgets;

use Filament\Widgets\Widget;

class CostInfoWidget extends Widget
{
    protected static string $view = 'filament.resources.product-resource.widgets.cost-info-widget';
    
    protected int | string | array $columnSpan = 'full';
}
