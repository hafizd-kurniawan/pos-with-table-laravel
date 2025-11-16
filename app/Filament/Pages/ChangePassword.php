<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class ChangePassword extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-key';
    
    protected static ?string $navigationLabel = 'Change Password';
    
    protected static ?string $title = 'Change Password';
    
    protected static ?string $navigationGroup = 'Settings';
    
    protected static ?int $navigationSort = 99;

    protected static string $view = 'filament.pages.change-password';
    
    public ?array $data = [];
    
    public function mount(): void
    {
        $this->form->fill();
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('current_password')
                    ->label('Current Password')
                    ->password()
                    ->revealable()
                    ->required()
                    ->currentPassword()
                    ->autocomplete('current-password'),
                
                TextInput::make('password')
                    ->label('New Password')
                    ->password()
                    ->revealable()
                    ->required()
                    ->rule(Password::min(8))
                    ->same('password_confirmation')
                    ->autocomplete('new-password')
                    ->helperText('Minimum 8 characters'),
                
                TextInput::make('password_confirmation')
                    ->label('Confirm New Password')
                    ->password()
                    ->revealable()
                    ->required()
                    ->autocomplete('new-password')
                    ->dehydrated(false),
            ])
            ->statePath('data');
    }
    
    public function submit(): void
    {
        $data = $this->form->getState();
        
        $user = Auth::user();
        
        // Update password
        $user->password = Hash::make($data['password']);
        $user->save();
        
        // Show success notification
        Notification::make()
            ->title('Password Changed!')
            ->body('Your password has been changed successfully.')
            ->success()
            ->send();
        
        // Reset form
        $this->form->fill();
    }
    
    protected function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('submit')
                ->label('Change Password')
                ->submit('submit')
                ->color('primary')
                ->icon('heroicon-o-check'),
        ];
    }
}
