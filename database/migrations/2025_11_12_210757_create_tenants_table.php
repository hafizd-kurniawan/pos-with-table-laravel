<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Created: 2025-11-13 04:07:58 WIB
     * Purpose: Multi-tenant SAAS - Tenants table with trial, subscription, and payment gateway configs
     */
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            
            // Basic Info
            $table->string('subdomain')->unique()->comment('Unique subdomain for tenant (e.g., resto-a)');
            $table->string('business_name')->comment('Restaurant/business name');
            $table->string('email')->unique()->comment('Primary contact email');
            $table->string('phone', 50)->nullable();
            $table->text('address')->nullable();
            $table->string('logo')->nullable()->comment('Logo file path');
            
            // Trial & Subscription Management
            $table->enum('status', ['trial', 'active', 'expired', 'suspended'])
                  ->default('trial')
                  ->comment('Current tenant status');
            $table->timestamp('trial_starts_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable()->comment('Trial expiry date (default 3 days)');
            $table->integer('trial_extension_days')->default(0)->comment('Additional trial days granted by admin');
            
            $table->string('subscription_plan', 50)->nullable()->comment('Current plan: bronze/silver/gold/platinum');
            $table->timestamp('subscription_starts_at')->nullable();
            $table->timestamp('subscription_ends_at')->nullable();
            
            // Payment Info (for subscription payment to SAAS owner)
            $table->decimal('amount_paid', 10, 2)->default(0)->comment('Last payment amount');
            $table->string('payment_method', 50)->nullable()->comment('Payment method used');
            $table->string('payment_proof')->nullable()->comment('Payment proof file path');
            
            // === TENANT CONFIGURABLE SETTINGS (Managed by Tenant Admin) === //
            
            // Midtrans Payment Gateway Configuration
            $table->string('midtrans_merchant_id')->nullable()->comment('Tenant\'s Midtrans merchant ID');
            $table->text('midtrans_server_key')->nullable()->comment('Encrypted Midtrans server key');
            $table->text('midtrans_client_key')->nullable()->comment('Encrypted Midtrans client key');
            $table->boolean('midtrans_is_production')->default(false)->comment('Midtrans environment');
            
            // N8N Webhook Configuration
            $table->string('n8n_webhook_url', 500)->nullable()->comment('Tenant\'s N8N webhook URL for notifications');
            $table->string('notification_phone', 50)->nullable()->comment('WhatsApp number for notifications');
            $table->json('notification_settings')->nullable()->comment('Additional notification preferences');
            
            // Firebase FCM Configuration
            $table->string('firebase_project_id')->nullable()->comment('Firebase project ID');
            $table->text('firebase_credentials')->nullable()->comment('Encrypted Firebase service account JSON');
            $table->string('firebase_database_url', 500)->nullable()->comment('Firebase Realtime Database URL');
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index('subdomain');
            $table->index('status');
            $table->index('subscription_ends_at');
            $table->index(['status', 'trial_ends_at']);
            $table->index(['status', 'subscription_ends_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
