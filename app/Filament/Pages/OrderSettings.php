<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use App\Models\Setting;
use App\Models\Discount;
use App\Models\Tax;

class OrderSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    
    protected static string $view = 'filament.pages.order-settings';
    
    protected static ?string $navigationLabel = 'Order Settings';
    
    protected static ?string $title = 'Order Settings';
    
    protected static ?int $navigationSort = 99;
    
    protected static ?string $navigationGroup = 'Settings';

    public ?array $data = [];

    public function mount(): void
    {
        // Get main settings record
        $setting = Setting::where('key', 'order_calculation')->first();
        
        $this->form->fill([
            'selected_discount_ids' => $setting->selected_discount_ids ?? [],
            'selected_tax_ids' => $setting->selected_tax_ids ?? [],
            'selected_service_ids' => $setting->selected_service_ids ?? [],
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Order Calculation Settings')
                    ->description('Select multiple discounts, taxes, and service charges (Customers will see only selected items)')
                    ->schema([
                        Select::make('selected_discount_ids')
                            ->label('🎁 Available Discounts for Customers')
                            ->helperText('Select multiple discounts that customers can choose at checkout')
                            ->options(Discount::active()->get()->mapWithKeys(function($discount) {
                                $label = $discount->type === 'percentage' 
                                    ? "{$discount->name} ({$discount->value}% OFF)" 
                                    : "{$discount->name} (Rp" . number_format($discount->value) . " OFF)";
                                return [$discount->id => $label];
                            }))
                            ->multiple()
                            ->searchable()
                            ->placeholder('No discounts (customers cannot select any discount)')
                            ->columnSpanFull(),
                        
                        Select::make('selected_tax_ids')
                            ->label('🧾 Tax Rates (PPN)')
                            ->helperText('Select multiple tax rates to apply (will be summed)')
                            ->options(Tax::active()->pajak()->get()->mapWithKeys(function($tax) {
                                return [$tax->id => "{$tax->name} ({$tax->value}%)"];
                            }))
                            ->multiple()
                            ->searchable()
                            ->placeholder('No tax (disabled)')
                            ->columnSpanFull(),
                        
                        Select::make('selected_service_ids')
                            ->label('💼 Service Charges')
                            ->helperText('Select multiple service charges to apply (will be summed)')
                            ->options(Tax::active()->layanan()->get()->mapWithKeys(function($service) {
                                return [$service->id => "{$service->name} ({$service->value}%)"];
                            }))
                            ->multiple()
                            ->searchable()
                            ->placeholder('No service charge (disabled)')
                            ->columnSpanFull(),
                    ])
                    ->columns(1),
            ])
            ->statePath('data');
    }

    protected function getSetting(string $key): bool
    {
        $setting = Setting::where('key', $key)->first();
        return $setting ? (bool) $setting->value : false;
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // Get or create main settings record
        $setting = Setting::firstOrCreate(
            ['key' => 'order_calculation'],
            ['value' => '1', 'label' => 'Order Calculation Settings']
        );

        // Update selected IDs (arrays)
        $setting->update([
            'selected_discount_ids' => $data['selected_discount_ids'] ?? [],
            'selected_tax_ids' => $data['selected_tax_ids'] ?? [],
            'selected_service_ids' => $data['selected_service_ids'] ?? [],
        ]);

        Notification::make()
            ->title('Settings saved successfully')
            ->success()
            ->send();

        // Refresh the form
        $this->mount();
    }
}
