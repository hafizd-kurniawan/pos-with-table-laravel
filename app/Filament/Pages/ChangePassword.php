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
    
    public static function getNavigationLabel(): string
    {
        return __('resource.change_password.navigation_label');
    }
    
    public function getTitle(): string
    {
        return __('resource.change_password.title');
    }
    
    public static function getNavigationGroup(): ?string
    {
        return __('resource.general.navigation.settings');
    }
    
    protected static ?int $navigationSort = 4;

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
                    ->label(__('resource.change_password.current_password'))
                    ->password()
                    ->revealable()
                    ->required()
                    ->currentPassword()
                    ->autocomplete('current-password'),
                
                TextInput::make('password')
                    ->label(__('resource.change_password.new_password'))
                    ->password()
                    ->revealable()
                    ->required()
                    ->rule(Password::min(8))
                    ->same('password_confirmation')
                    ->autocomplete('new-password')
                    ->helperText(__('resource.change_password.helper_text')),
                
                TextInput::make('password_confirmation')
                    ->label(__('resource.change_password.confirm_password'))
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
            ->title(__('resource.change_password.notifications.success_title'))
            ->body(__('resource.change_password.notifications.success_body'))
            ->success()
            ->send();
        
        // Reset form
        $this->form->fill();
    }
    
    protected function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('submit')
                ->label(__('resource.change_password.submit'))
                ->submit('submit')
                ->color('primary')
                ->icon('heroicon-o-check'),
        ];
    }
}
