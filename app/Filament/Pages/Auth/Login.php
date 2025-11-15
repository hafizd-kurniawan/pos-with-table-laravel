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

            // CRITICAL: Check tenant status BEFORE login
            $tenant = \App\Models\Tenant::find($user->tenant_id);
            
            if (!$tenant) {
                throw ValidationException::withMessages([
                    'data.email' => '❌ Your tenant account was not found. Please contact support.',
                ]);
            }

            // Check if suspended
            if ($tenant->status === 'suspended') {
                \Log::warning('Login attempt by suspended tenant', [
                    'user_id' => $user->id,
                    'tenant_id' => $tenant->id,
                    'subdomain' => $tenant->subdomain,
                ]);
                
                throw ValidationException::withMessages([
                    'data.email' => '🚫 Your account is SUSPENDED. Please contact support at support@possaas.com for assistance.',
                ]);
            }

            // Check if trial expired
            if ($tenant->status === 'trial' && $tenant->trial_ends_at && $tenant->trial_ends_at < now()) {
                // Auto-expire
                $tenant->update(['status' => 'expired']);
                
                \Log::warning('Login attempt by expired trial tenant', [
                    'user_id' => $user->id,
                    'tenant_id' => $tenant->id,
                    'subdomain' => $tenant->subdomain,
                    'trial_ended' => $tenant->trial_ends_at->format('Y-m-d H:i:s'),
                ]);
                
                throw ValidationException::withMessages([
                    'data.email' => '⏰ Your trial period has EXPIRED (' . $tenant->trial_ends_at->diffForHumans() . '). Please contact your administrator to renew your subscription.',
                ]);
            }

            // Check if subscription expired
            if ($tenant->status === 'active' && $tenant->subscription_ends_at && $tenant->subscription_ends_at < now()) {
                // Auto-expire
                $tenant->update(['status' => 'expired']);
                
                \Log::warning('Login attempt by expired subscription tenant', [
                    'user_id' => $user->id,
                    'tenant_id' => $tenant->id,
                    'subdomain' => $tenant->subdomain,
                    'subscription_ended' => $tenant->subscription_ends_at->format('Y-m-d H:i:s'),
                ]);
                
                throw ValidationException::withMessages([
                    'data.email' => '⏰ Your subscription has EXPIRED (' . $tenant->subscription_ends_at->diffForHumans() . '). Please contact your administrator to renew.',
                ]);
            }

            // Check expired status
            if ($tenant->status === 'expired') {
                $expiredDate = $tenant->subscription_ends_at ?? $tenant->trial_ends_at;
                $expiredText = $expiredDate ? ' (expired ' . $expiredDate->diffForHumans() . ')' : '';
                
                \Log::warning('Login attempt by expired tenant', [
                    'user_id' => $user->id,
                    'tenant_id' => $tenant->id,
                    'subdomain' => $tenant->subdomain,
                ]);
                
                throw ValidationException::withMessages([
                    'data.email' => '⏰ Your subscription has EXPIRED' . $expiredText . '. Please contact your administrator to renew your subscription.',
                ]);
            }

            // Verify password
            if (!\Hash::check($data['password'], $user->password)) {
                throw ValidationException::withMessages([
                    'data.email' => __('filament-panels::pages/auth/login.messages.failed'),
                ]);
            }

            // All checks passed - allow login
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
