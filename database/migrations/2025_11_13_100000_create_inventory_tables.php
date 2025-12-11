<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. SUPPLIERS (Pemasok)
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('name');
            $table->string('code')->unique(); // SUP-001
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('contact_person')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
        });

        // 2. INGREDIENTS (Bahan Baku)
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('name');
            $table->string('sku')->unique(); // ING-001
            $table->string('unit'); // kg, liter, pcs, gram, ml
            $table->decimal('current_stock', 15, 3)->default(0); // 15 digits, 3 decimal
            $table->decimal('min_stock', 15, 3)->default(0); // Minimum stock alert threshold
            $table->decimal('max_stock', 15, 3)->nullable(); // Maximum stock (optional)
            $table->decimal('cost_per_unit', 15, 2)->default(0); // Harga beli per unit
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->onDelete('set null');
            $table->string('category')->nullable(); // Bumbu, Sayuran, Protein, dll
            $table->string('image')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index('sku');
        });

        // 3. PURCHASE ORDERS (Order Pembelian)
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('restrict');
            $table->string('po_number')->unique(); // PO-202511-0001
            $table->date('order_date');
            $table->date('expected_delivery_date')->nullable();
            $table->date('actual_delivery_date')->nullable();
            $table->enum('status', ['draft', 'sent', 'received', 'cancelled'])->default('draft');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('shipping_cost', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('received_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index('po_number');
            $table->index('order_date');
        });

        // 4. PURCHASE ORDER ITEMS (Detail PO)
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->onDelete('cascade');
            $table->foreignId('ingredient_id')->constrained('ingredients')->onDelete('restrict');
            $table->decimal('quantity', 15, 3);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('subtotal', 15, 2); // quantity * unit_price
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('purchase_order_id');
        });

        // 5. STOCK MOVEMENTS (Mutasi Stok)
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('ingredient_id')->constrained('ingredients')->onDelete('cascade');
            $table->enum('type', ['in', 'out', 'adjustment']); // masuk, keluar, penyesuaian
            $table->decimal('quantity', 15, 3); // Always positive
            $table->decimal('stock_before', 15, 3); // Stock before movement
            $table->decimal('stock_after', 15, 3); // Stock after movement
            $table->string('reference_type')->nullable(); // order, purchase_order, manual_adjustment, stock_opname, waste
            $table->unsignedBigInteger('reference_id')->nullable(); // ID of reference
            $table->foreignId('user_id')->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamp('moved_at');
            $table->timestamps();

            $table->index(['tenant_id', 'ingredient_id', 'moved_at']);
            $table->index(['reference_type', 'reference_id']);
        });

        // 6. RECIPES (Resep - Link Product to Ingredients)
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('ingredient_id')->constrained('ingredients')->onDelete('restrict');
            $table->decimal('quantity_needed', 15, 3); // Quantity needed per 1 product
            $table->text('notes')->nullable(); // e.g., "Potong dadu kecil"
            $table->timestamps();

            $table->index('product_id');
            $table->unique(['product_id', 'ingredient_id']); // Each ingredient only once per product
        });

        // 7. STOCK OPNAMES (Stock Taking / Physical Count)
        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('opname_number')->unique(); // SO-202511-0001
            $table->date('opname_date');
            $table->enum('status', ['draft', 'completed'])->default('draft');
            $table->foreignId('user_id')->constrained('users'); // Who did the count
            $table->text('notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'opname_date']);
        });

        // 8. STOCK OPNAME ITEMS (Detail Stock Taking)
        Schema::create('stock_opname_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_opname_id')->constrained('stock_opnames')->onDelete('cascade');
            $table->foreignId('ingredient_id')->constrained('ingredients')->onDelete('restrict');
            $table->decimal('system_qty', 15, 3); // Qty from system
            $table->decimal('physical_qty', 15, 3); // Actual count
            $table->decimal('difference', 15, 3); // physical - system
            $table->text('notes')->nullable(); // Reason for difference
            $table->timestamps();

            $table->index('stock_opname_id');
            $table->unique(['stock_opname_id', 'ingredient_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_opname_items');
        Schema::dropIfExists('stock_opnames');
        Schema::dropIfExists('recipes');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('ingredients');
        Schema::dropIfExists('suppliers');
    }
};
