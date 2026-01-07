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
    
    public static function getNavigationLabel(): string
    {
        return __('resource.order_settings.navigation_label');
    }
    
    public function getTitle(): string
    {
        return __('resource.order_settings.title');
    }
    
    public static function getNavigationGroup(): ?string
    {
        return __('resource.general.navigation.settings');
    }
    
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
                Section::make(__('resource.order_settings.section.heading'))
                    ->description(__('resource.order_settings.section.description'))
                    ->schema([
                        CheckboxList::make('selected_discount_ids')
                            ->label(__('resource.order_settings.fields.discounts'))
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
                                        $value = \App\Helpers\FormatHelper::formatNumber($discount->value, 0);
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
                                        $desc = $discount->description ?? __('resource.order_settings.helpers.no_description');
                                        return [$discount->id => $desc];
                                    })
                                    ->toArray();
                            })
                            ->columns(2)
                            ->gridDirection('row')
                            ->bulkToggleable()
                            ->helperText(__('resource.order_settings.helpers.discounts')),

                        CheckboxList::make('selected_tax_ids')
                            ->label(__('resource.order_settings.fields.taxes'))
                            ->options(function () {
                                return Tax::where('status', 'active')
                                    ->where('type', 'pajak')
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(function ($tax) {
                                        $value = \App\Helpers\FormatHelper::formatNumber($tax->value, 0);
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
                                        $desc = $tax->description ?? __('resource.order_settings.helpers.tax_desc');
                                        return [$tax->id => $desc];
                                    })
                                    ->toArray();
                            })
                            ->columns(2)
                            ->gridDirection('row')
                            ->bulkToggleable()
                            ->helperText(__('resource.order_settings.helpers.taxes')),

                        CheckboxList::make('selected_service_ids')
                            ->label(__('resource.order_settings.fields.services'))
                            ->options(function () {
                                return Tax::where('status', 'active')
                                    ->where('type', 'layanan')
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(function ($service) {
                                        $value = \App\Helpers\FormatHelper::formatNumber($service->value, 0);
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
                                        $desc = $service->description ?? __('resource.order_settings.helpers.service_desc');
                                        return [$service->id => $desc];
                                    })
                                    ->toArray();
                            })
                            ->columns(2)
                            ->gridDirection('row')
                            ->columns(2)
                            ->gridDirection('row')
                            ->bulkToggleable()
                            ->helperText(__('resource.order_settings.helpers.services')),
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
            ->title(__('resource.order_settings.notifications.success_title'))
            ->body(__('resource.order_settings.notifications.success_body'))
            ->success()
            ->send();
    }
}
