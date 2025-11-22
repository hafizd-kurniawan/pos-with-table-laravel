<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use App\Models\Setting;
use App\Models\Discount;
use App\Models\Tax;
use Illuminate\Support\Facades\Cache;

class OrderSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    
    protected static ?string $navigationLabel = 'Order Settings';
    
    protected static ?string $title = 'Order Settings';
    
    protected static ?string $navigationGroup = 'Settings';
    
    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.order-settings';

    // Form data
    public ?array $data = [];

    public function mount(): void
    {
        // Get current selected IDs from settings
        $discountSetting = Setting::where('key', 'selected_discount_ids')->first();
        $taxSetting = Setting::where('key', 'selected_tax_ids')->first();
        $serviceSetting = Setting::where('key', 'selected_service_ids')->first();

        $this->form->fill([
            'selected_discount_ids' => $discountSetting ? json_decode($discountSetting->value, true) ?? [] : [],
            'selected_tax_ids' => $taxSetting ? json_decode($taxSetting->value, true) ?? [] : [],
            'selected_service_ids' => $serviceSetting ? json_decode($serviceSetting->value, true) ?? [] : [],
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Select Items to Show in POS & Self-Order')
                    ->description('Choose which specific items will be available for customers to select')
                    ->schema([
                        CheckboxList::make('selected_discount_ids')
                            ->label('🎁 Discounts')
                            ->options(function () {
                                return Discount::where('status', 'active')
                                    ->where(function($query) {
                                        $query->whereNull('expired_date')
                                              ->orWhere('expired_date', '>', now());
                                    })
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(function ($discount) {
                                        $type = $discount->type === 'percentage' ? '%' : 'Rp';
                                        $value = number_format($discount->value, 0, ',', '.');
                                        return [$discount->id => "{$discount->name} ({$value}{$type})"];
                                    })
                                    ->toArray();
                            })
                            ->descriptions(function () {
                                return Discount::where('status', 'active')
                                    ->where(function($query) {
                                        $query->whereNull('expired_date')
                                              ->orWhere('expired_date', '>', now());
                                    })
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(function ($discount) {
                                        $desc = $discount->description ?? 'No description';
                                        return [$discount->id => $desc];
                                    })
                                    ->toArray();
                            })
                            ->columns(2)
                            ->gridDirection('row')
                            ->bulkToggleable()
                            ->helperText('Only checked discounts will appear in POS/Self-Order'),

                        CheckboxList::make('selected_tax_ids')
                            ->label('🧾 Taxes (PPN)')
                            ->options(function () {
                                return Tax::where('status', 'active')
                                    ->where('type', 'pajak')
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(function ($tax) {
                                        $value = number_format($tax->value, 0, ',', '.');
                                        return [$tax->id => "{$tax->name} ({$value}%)"];
                                    })
                                    ->toArray();
                            })
                            ->descriptions(function () {
                                return Tax::where('status', 'active')
                                    ->where('type', 'pajak')
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(function ($tax) {
                                        $desc = $tax->description ?? 'Tax applied to subtotal';
                                        return [$tax->id => $desc];
                                    })
                                    ->toArray();
                            })
                            ->columns(2)
                            ->gridDirection('row')
                            ->bulkToggleable()
                            ->helperText('Only checked taxes will appear in POS/Self-Order'),

                        CheckboxList::make('selected_service_ids')
                            ->label('💼 Service Charges')
                            ->options(function () {
                                return Tax::where('status', 'active')
                                    ->where('type', 'layanan')
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(function ($service) {
                                        $value = number_format($service->value, 0, ',', '.');
                                        return [$service->id => "{$service->name} ({$value}%)"];
                                    })
                                    ->toArray();
                            })
                            ->descriptions(function () {
                                return Tax::where('status', 'active')
                                    ->where('type', 'layanan')
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(function ($service) {
                                        $desc = $service->description ?? 'Service charge applied after tax';
                                        return [$service->id => $desc];
                                    })
                                    ->toArray();
                            })
                            ->columns(2)
                            ->gridDirection('row')
                            ->bulkToggleable()
                            ->helperText('Only checked services will appear in POS/Self-Order'),
                    ])
                    ->columns(1),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // Save selected IDs as JSON
        Setting::updateOrCreate(
            ['key' => 'selected_discount_ids'],
            [
                'value' => json_encode($data['selected_discount_ids'] ?? []),
                'label' => 'Selected Discount IDs',
                'type' => 'array',
                'group' => 'order',
            ]
        );

        Setting::updateOrCreate(
            ['key' => 'selected_tax_ids'],
            [
                'value' => json_encode($data['selected_tax_ids'] ?? []),
                'label' => 'Selected Tax IDs',
                'type' => 'array',
                'group' => 'order',
            ]
        );

        Setting::updateOrCreate(
            ['key' => 'selected_service_ids'],
            [
                'value' => json_encode($data['selected_service_ids'] ?? []),
                'label' => 'Selected Service IDs',
                'type' => 'array',
                'group' => 'order',
            ]
        );

        // Clear all cache
        Cache::flush();

        Notification::make()
            ->title('Order settings saved successfully!')
            ->body('Selected items will now appear in POS and Self-Order systems.')
            ->success()
            ->send();
    }
}
