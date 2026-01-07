<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Tenant;
use App\Models\User;

class TenantRegistrationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_can_register_new_tenant()
    {
        $response = $this->postJson('/api/register', [
            'business_name' => 'Test Cafe',
            'email' => 'admin@testcafe.com',
            'phone' => '08123456789',
            'address' => 'Jl. Test No. 1',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        if ($response->status() !== 201) {
            \Illuminate\Support\Facades\Log::error($response->content());
        }

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user',
                    'tenant',
                    'token',
                ]
            ]);

        $this->assertDatabaseHas('tenants', [
            'business_name' => 'Test Cafe',
            'subdomain' => 'test-cafe',
            'email' => 'admin@testcafe.com',
            'status' => 'trial',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'admin@testcafe.com',
            'name' => 'Admin Test Cafe',
        ]);

        // Verify settings created
        $tenant = Tenant::where('subdomain', 'test-cafe')->first();
        $this->assertDatabaseHas('settings', [
            'tenant_id' => $tenant->id,
            'key' => 'app_name',
        ]);
        
        // Verify roles created
        $this->assertDatabaseHas('roles', [
            'tenant_id' => $tenant->id,
            'name' => 'admin',
        ]);
    }

    public function test_cannot_register_with_existing_email()
    {
        User::factory()->create(['email' => 'existing@test.com']);

        $response = $this->postJson('/api/register', [
            'business_name' => 'Another Cafe',
            'email' => 'existing@test.com',
            'phone' => '08123456789',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /*
    public function test_subdomain_is_unique()
    {
        Tenant::create([
            'business_name' => 'Test Cafe',
            'subdomain' => 'test-cafe',
            'email' => 'first@test.com',
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/register', [
            'business_name' => 'Test Cafe',
            'email' => 'second@test.com',
            'phone' => '08123456789',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        if ($response->status() !== 201) {
            \Illuminate\Support\Facades\Log::error($response->content());
        }

        $response->assertStatus(201);
        
        $this->assertDatabaseHas('tenants', [
            'email' => 'second@test.com',
            'subdomain' => 'test-cafe-1',
        ]);
    }
    */
}
