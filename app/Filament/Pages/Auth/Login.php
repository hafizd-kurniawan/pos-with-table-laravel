<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Checkbox;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    /**
     * Override authenticate to ensure tenant isolation
     */
    public function authenticate(): ?LoginResponse
    {
        try {
            $data = $this->form->getState();

            // CRITICAL: Query user WITHOUT global scope
            $user = \App\Models\User::withoutGlobalScope('tenant')
                ->where('email', $data['email'])
                ->first();

            if (!$user) {
                throw ValidationException::withMessages([
                    'data.email' => __('filament-panels::pages/auth/login.messages.failed'),
                ]);
            }

            // Check if tenant admin (NOT super admin)
            if ($user->tenant_id === null) {
                throw ValidationException::withMessages([
                    'data.email' => 'Access denied. This panel is for tenant admins only.',
                ]);
            }

            // Verify password
            if (!\Hash::check($data['password'], $user->password)) {
                throw ValidationException::withMessages([
                    'data.email' => __('filament-panels::pages/auth/login.messages.failed'),
                ]);
            }

            // Login WITHOUT remember me to avoid scope issues
            auth()->login($user, remember: false);

            session()->regenerate();

            return app(LoginResponse::class);
        } catch (ValidationException $exception) {
            throw $exception;
        }
    }

    /**
     * Override form to remove remember me checkbox
     */
    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getEmailFormComponent(),
                        $this->getPasswordFormComponent(),
                        // Removed: $this->getRememberFormComponent()
                    ])
                    ->statePath('data'),
            ),
        ];
    }
}
