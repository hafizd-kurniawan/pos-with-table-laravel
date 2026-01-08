<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use App\Models\Setting;

class LoyaltySettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-gift';
    
    protected static ?string $navigationGroup = 'Loyalty Program';

    protected static ?string $title = 'Loyalty Settings';

    protected static string $view = 'filament.pages.loyalty-settings';

    public ?array $data = [];

    public function mount(): void
    {
        // Load existing settings or defaults
        $this->form->fill([
            'loyalty_earning_rate' => Setting::get('loyalty_earning_rate', 10000),
            'loyalty_redemption_value' => Setting::get('loyalty_redemption_value', 1),
            'loyalty_min_redemption' => Setting::get('loyalty_min_redemption', 0),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Earning Configuration')
                    ->description('Configure how customers earn points.')
                    ->schema([
                        Forms\Components\TextInput::make('loyalty_earning_rate')
                            ->label('Earning Rate (Rp)')
                            ->helperText('Amount customer needs to spend to earn 1 point.')
                            ->numeric()
                            ->required()
                            ->prefix('Rp')
                            ->default(10000),
                    ]),

                Forms\Components\Section::make('Redemption Configuration')
                    ->description('Configure how customers redeem points.')
                    ->schema([
                        Forms\Components\TextInput::make('loyalty_redemption_value')
                            ->label('Redemption Value (Rp)')
                            ->helperText('The value of 1 point in Rupiah when redeeming.')
                            ->numeric()
                            ->required()
                            ->prefix('Rp')
                            ->default(1),
                        
                        Forms\Components\TextInput::make('loyalty_min_redemption')
                            ->label('Minimum Redemption Points')
                            ->helperText('Minimum points required to start redeeming.')
                            ->numeric()
                            ->required()
                            ->default(0),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Changes')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();

            // Save Earning Rate
            Setting::updateOrCreate(
                ['key' => 'loyalty_earning_rate'],
                [
                    'value' => $data['loyalty_earning_rate'],
                    'type' => 'number',
                    'group' => 'loyalty',
                    'label' => 'Amount to Earn 1 Point',
                ]
            );

            // Save Redemption Value
            Setting::updateOrCreate(
                ['key' => 'loyalty_redemption_value'],
                [
                    'value' => $data['loyalty_redemption_value'],
                    'type' => 'number',
                    'group' => 'loyalty',
                    'label' => 'Redemption Value per Point',
                ]
            );

            // Save Min Redemption
            Setting::updateOrCreate(
                ['key' => 'loyalty_min_redemption'],
                [
                    'value' => $data['loyalty_min_redemption'],
                    'type' => 'number',
                    'group' => 'loyalty',
                    'label' => 'Minimum Points to Redeem',
                ]
            );

            Notification::make() 
                ->success()
                ->title('Settings saved successfully')
                ->send();

        } catch (\Exception $e) {
            Notification::make() 
                ->danger()
                ->title('Failed to save settings')
                ->body($e->getMessage())
                ->send();
        }
    }
}
