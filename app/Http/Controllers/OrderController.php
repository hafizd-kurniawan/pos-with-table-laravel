<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Table;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Str;
use Midtrans\CoreApi;
use Midtrans\Notification;
// use Midtrans\Transaction;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\Category;
use Illuminate\Support\Facades\Http;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use App\Models\User;
use App\Models\Tenant;
use App\Traits\ManagesStock;

class OrderController extends Controller
{
    use ManagesStock;
    // Tampil menu berdasarkan nomor meja
    // UUID-based tenant identification for security
    public function index($tenantIdentifier, $tablenumber)
    {
        // Parse tenant identifier (format: slug-shortUuid)
        $tenant = $this->getTenantFromIdentifier($tenantIdentifier);
        
        // Get table for this specific tenant
        $table = Table::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->where('name', $tablenumber)
            ->firstOrFail();
        
        // Add tenant identifier to table for views
        $table->tenantIdentifier = "{$tenant->slug}-{$tenant->short_uuid}";
        
        // Get products & categories for THIS tenant only
        $allProducts = \App\Models\Product::withoutGlobalScope('tenant')
            ->where('tenant_id', $table->tenant_id)
            ->where('status', 'available')
            ->orderBy('name')
            ->get();
        
        $allCategory = (object)[
            'id' => 0,
            'name' => 'All',
            'products' => $allProducts
        ];
        
        $categories = Category::withoutGlobalScope('tenant')
            ->where('tenant_id', $table->tenant_id)
            ->with(['products' => function ($q) use ($table) {
                $q->withoutGlobalScope('tenant')
                    ->where('tenant_id', $table->tenant_id)
                    ->where('status', 'available')
                    ->orderBy('name');
            }])
            ->get();
        
        $categories = collect([$allCategory])->concat($categories);
        return view('order.menu', compact('table', 'categories'));
    }


    // Tambah ke keranjang (session) dari menu page
    public function addToCart(Request $request, $tenantIdentifier, $tablenumber)
    {
        $tenant = $this->getTenantFromIdentifier($tenantIdentifier);
        try {
            $product = Product::findOrFail($request->input('product_id'));
            $qtyChange = (int) $request->input('qty', 1);

            // Validasi stock real-time dengan locking
            $errors = $this->validateStockAvailability([
                ['product_id' => $product->id, 'quantity' => $qtyChange]
            ]);

            if (!empty($errors)) {
                return redirect()->back()
                    ->with('error', $errors[0])
                    ->with('selectedCategory', $request->input('category_id', 0));
            }

            $cartKey = 'cart_' . $tenantIdentifier . '_' . $tablenumber;
            $cart = session($cartKey, []);

            // Cari index produk yang sama
            $foundIndex = null;
            $currentQtyInCart = 0;
            foreach ($cart as $i => $item) {
                if (($item['product_id'] ?? null) == $product->id) {
                    $foundIndex = $i;
                    $currentQtyInCart = $item['qty'];
                    break;
                }
            }

            $newTotalQty = $currentQtyInCart + $qtyChange;

            // Double check total qty dengan stock terkini
            $finalErrors = $this->validateStockAvailability([
                ['product_id' => $product->id, 'quantity' => $newTotalQty]
            ]);

            if (!empty($finalErrors)) {
                return redirect()->back()
                    ->with('error', "Cannot add {$qtyChange} items. " . $finalErrors[0])
                    ->with('selectedCategory', $request->input('category_id', 0));
            }

            if ($foundIndex !== null) {
                $cart[$foundIndex]['qty'] = $newTotalQty;
                if ($cart[$foundIndex]['qty'] <= 0) {
                    unset($cart[$foundIndex]);
                }
            } else {
                if ($qtyChange > 0) {
                    $cart[] = [
                        'product_id' => $product->id,
                        'name'       => $product->name,
                        'qty'        => $qtyChange,
                        'price'      => $product->price,
                        'note'       => $request->input('note', ''),
                    ];
                }
            }

            $cart = array_values($cart);
            session([$cartKey => $cart]);

            return redirect()->back()
                ->with('success', "Added {$qtyChange} {$product->name} to cart")
                ->with('selectedCategory', $request->input('category_id', 0));

        } catch (\Exception $e) {
            Log::error('Error adding to cart', [
                'product_id' => $request->input('product_id'),
                'table' => $tablenumber,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'Failed to add item to cart. Please try again.')
                ->with('selectedCategory', $request->input('category_id', 0));
        }
    }

    // AJAX add to cart - no page refresh
    // AJAX add to cart - no page refresh
    public function addToCartAjax(Request $request, $tenantIdentifier, $tablenumber)
    {
        try {
            $tenant = $this->getTenantFromIdentifier($tenantIdentifier);
            
            $product = Product::withoutGlobalScope('tenant')
                ->where('tenant_id', $tenant->id)
                ->findOrFail($request->input('product_id'));
                
            $qtyChange = (int) $request->input('qty', 1);

            // Validasi stock real-time dengan locking
            $errors = $this->validateStockAvailability([
                ['product_id' => $product->id, 'quantity' => $qtyChange]
            ]);

            if (!empty($errors)) {
                return response()->json([
                    'success' => false,
                    'message' => $errors[0],
                    'type' => 'error'
                ], 400);
            }

            $cartKey = 'cart_' . $tenantIdentifier . '_' . $tablenumber;
            $cart = session($cartKey, []);

            // Debug: Log current cart state BEFORE any changes
            Log::info('AJAX ADD CART - BEFORE', [
                'tenant' => $tenantIdentifier,
                'table' => $tablenumber,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'cart_key' => $cartKey,
                'current_cart_count' => count($cart),
            ]);

            // Cari index produk yang sama
            $foundIndex = null;
            $currentQtyInCart = 0;
            foreach ($cart as $i => $item) {
                if (($item['product_id'] ?? null) == $product->id) {
                    $foundIndex = $i;
                    $currentQtyInCart = $item['qty'];
                    break;
                }
            }

            $newTotalQty = $currentQtyInCart + $qtyChange;

            // Double check total qty dengan stock terkini
            $finalErrors = $this->validateStockAvailability([
                ['product_id' => $product->id, 'quantity' => $newTotalQty]
            ]);

            if (!empty($finalErrors)) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot add {$qtyChange} items. " . $finalErrors[0],
                    'type' => 'error'
                ], 400);
            }

            if ($foundIndex !== null) {
                $cart[$foundIndex]['qty'] = $newTotalQty;
                // Update note if provided
                if ($request->has('note')) {
                    $cart[$foundIndex]['note'] = $request->input('note');
                }
                if ($cart[$foundIndex]['qty'] <= 0) {
                    unset($cart[$foundIndex]);
                }
            } else {
                if ($qtyChange > 0) {
                    $cart[] = [
                        'product_id' => $product->id,
                        'name'       => $product->name,
                        'qty'        => $qtyChange,
                        'price'      => $product->price,
                        'note'       => $request->input('note', ''),
                    ];
                }
            }

            $cart = array_values($cart);
            session([$cartKey => $cart]);

            // Calculate cart totals
            $cartTotal = collect($cart)->sum(fn($item) => $item['price'] * $item['qty']);
            $cartItemCount = collect($cart)->sum('qty');

            return response()->json([
                'success' => true,
                'message' => "Added {$qtyChange} {$product->name} to cart",
                'type' => 'success',
                'cart' => [
                    'total' => $cartTotal,
                    'item_count' => $cartItemCount,
                    'items' => $cart
                ],
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'qty_added' => $qtyChange
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error adding to cart via AJAX', [
                'product_id' => $request->input('product_id'),
                'table' => $tablenumber,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to add item to cart. Please try again.',
                'type' => 'error'
            ], 500);
        }
    }

    // Lihat keranjang
    public function cart($tenantIdentifier, $tablenumber)
    {
        $tenant = $this->getTenantFromIdentifier($tenantIdentifier);
        $cart = session('cart_' . $tenantIdentifier . '_' . $tablenumber, []);
        $table = Table::where('tenant_id', $tenant->id)
            ->where('name', $tablenumber)
            ->firstOrFail();
        
        // Add tenant identifier to table for views
        $table->tenantIdentifier = $tenantIdentifier;
        
        // Validasi stock untuk semua items di cart
        $stockValidation = $this->validateCartStock($cart);

        // Fetch settings for cart estimation
        $selectedDiscounts = collect();
        $selectedTaxes = collect();
        $selectedServices = collect();

        try {
            $discountIds = json_decode(\App\Models\Setting::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->where('key', 'selected_discount_ids')->value('value') ?? '[]', true);
            $taxIds = json_decode(\App\Models\Setting::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->where('key', 'selected_tax_ids')->value('value') ?? '[]', true);
            $serviceIds = json_decode(\App\Models\Setting::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->where('key', 'selected_service_ids')->value('value') ?? '[]', true);

            if (!empty($discountIds)) {
                $selectedDiscounts = \App\Models\Discount::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->where('status', 'active')->whereIn('id', $discountIds)->get();
            }
            if (!empty($taxIds)) {
                $selectedTaxes = \App\Models\Tax::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->where('status', 'active')->where('type', 'pajak')->whereIn('id', $taxIds)->get();
            }
            if (!empty($serviceIds)) {
                $selectedServices = \App\Models\Tax::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->where('status', 'active')->where('type', 'layanan')->whereIn('id', $serviceIds)->get();
            }
        } catch (\Exception $e) {
            Log::error('Error fetching cart settings: ' . $e->getMessage());
        }

        $autoTax = $selectedTaxes->first();
        $autoService = $selectedServices->first();
        
        return view('order.cart', compact('cart', 'table', 'stockValidation', 'autoTax', 'autoService'));
    }

    // Form checkout (isi nama/phone opsional)
    public function checkoutForm($tenantIdentifier, $tablenumber)
    {
        $tenant = $this->getTenantFromIdentifier($tenantIdentifier);
        $cart = session('cart_' . $tenantIdentifier . '_' . $tablenumber, []);
        $table = Table::where('tenant_id', $tenant->id)
            ->where('name', $tablenumber)
            ->firstOrFail();
        
        // Add tenant identifier to table for views
        $table->tenantIdentifier = $tenantIdentifier;
        
        // Pre-validate cart sebelum tampilkan form checkout
        $stockValidation = $this->validateCartStock($cart);
        
        if (!$stockValidation['is_valid']) {
            return redirect()->route('order.cart', [$tenantIdentifier, $tablenumber])
                ->with('error', 'Some items in your cart have stock issues: ' . implode(', ', $stockValidation['errors']));
        }
        
        // Explicitly fetch settings for THIS tenant
        $selectedDiscounts = collect();
        $selectedTaxes = collect();
        $selectedServices = collect();

        try {
            // Get raw settings directly from DB to avoid global scope issues
            $discountIds = json_decode(\App\Models\Setting::withoutGlobalScope('tenant')
                ->where('tenant_id', $tenant->id)
                ->where('key', 'selected_discount_ids')
                ->value('value') ?? '[]', true);

            $taxIds = json_decode(\App\Models\Setting::withoutGlobalScope('tenant')
                ->where('tenant_id', $tenant->id)
                ->where('key', 'selected_tax_ids')
                ->value('value') ?? '[]', true);

            $serviceIds = json_decode(\App\Models\Setting::withoutGlobalScope('tenant')
                ->where('tenant_id', $tenant->id)
                ->where('key', 'selected_service_ids')
                ->value('value') ?? '[]', true);

            if (!empty($discountIds)) {
                $selectedDiscounts = \App\Models\Discount::withoutGlobalScope('tenant')
                    ->where('tenant_id', $tenant->id)
                    ->where('status', 'active')
                    ->where(function($query) {
                        $query->whereNull('expired_date')
                              ->orWhere('expired_date', '>', now());
                    })
                    ->whereIn('id', $discountIds)
                    ->orderBy('name')
                    ->get();
            }

            if (!empty($taxIds)) {
                $selectedTaxes = \App\Models\Tax::withoutGlobalScope('tenant')
                    ->where('tenant_id', $tenant->id)
                    ->where('status', 'active')
                    ->where('type', 'pajak')
                    ->whereIn('id', $taxIds)
                    ->orderBy('name')
                    ->get();
            }

            if (!empty($serviceIds)) {
                $selectedServices = \App\Models\Tax::withoutGlobalScope('tenant')
                    ->where('tenant_id', $tenant->id)
                    ->where('status', 'active')
                    ->where('type', 'layanan')
                    ->whereIn('id', $serviceIds)
                    ->orderBy('name')
                    ->get();
            }
        } catch (\Exception $e) {
            Log::error('Error fetching checkout settings: ' . $e->getMessage());
        }
        
        $autoTax = $selectedTaxes->first();
        $autoService = $selectedServices->first();
        
        return view('order.checkout', compact('cart', 'table', 'selectedDiscounts', 'selectedTaxes', 'selectedServices', 'autoTax', 'autoService'));
    }

    /**
     * Configure Midtrans API credentials from tenant settings
     * PUBLIC ACCESS: Works for self-order (no auth required)
     */
    private function configureMidtrans($tenantId = null)
    {
        // Get tenant_id from parameter, auth user, or throw error
        if (!$tenantId) {
            $userId = auth()->id();
            if ($userId) {
                $user = \DB::table('users')->where('id', $userId)->first();
                $tenantId = $user->tenant_id ?? null;
            }
        }
        
        if (!$tenantId) {
            throw new \Exception('Tenant not identified. Cannot configure payment gateway.');
        }
        
        // Get Midtrans settings from key-value table
        $serverKey = \App\Models\Setting::where('tenant_id', $tenantId)
            ->where('key', 'midtrans_server_key')
            ->value('value');
            
        $clientKey = \App\Models\Setting::where('tenant_id', $tenantId)
            ->where('key', 'midtrans_client_key')
            ->value('value');
            
        $isProduction = \App\Models\Setting::where('tenant_id', $tenantId)
            ->where('key', 'midtrans_is_production')
            ->value('value');
        
        if (!$serverKey || !$clientKey) {
            throw new \Exception('Midtrans credentials not configured. Please set Server Key and Client Key in Settings.');
        }
        
        // Set Midtrans configuration
        \Midtrans\Config::$serverKey = $serverKey;
        \Midtrans\Config::$clientKey = $clientKey;
        \Midtrans\Config::$isProduction = (bool) $isProduction;
        \Midtrans\Config::$isSanitized = true;
        \Midtrans\Config::$is3ds = true;
        
        Log::info('MIDTRANS: Configuration set', [
            'tenant_id' => $tenantId,
            'is_production' => \Midtrans\Config::$isProduction,
            'server_key_prefix' => substr($serverKey, 0, 10) . '...',
        ]);
    }

    /**
     * Validate semua items di cart untuk stock availability
     */
    private function validateCartStock($cart)
    {
        if (empty($cart)) {
            return ['is_valid' => false, 'errors' => ['Cart is empty']];
        }

        try {
            // Convert cart items ke format yang dibutuhkan
            $items = collect($cart)->map(function ($item) {
                return [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['qty']
                ];
            })->toArray();

            $errors = $this->validateStockAvailability($items);
            
            return [
                'is_valid' => empty($errors),
                'errors' => $errors,
                'items_count' => count($cart),
                'total_qty' => collect($cart)->sum('qty')
            ];
        } catch (\Exception $e) {
            Log::error('Error validating cart stock', [
                'cart' => $cart,
                'error' => $e->getMessage()
            ]);
            
            return [
                'is_valid' => false, 
                'errors' => ['Unable to validate cart stock. Please try again.']
            ];
        }
    }

    public function removeCart($tenantIdentifier, $tablenumber, $productId)
    {
        $cartKey = 'cart_' . $tenantIdentifier . '_' . $tablenumber;
        $cart = session($cartKey, []);
        foreach ($cart as $i => $item) {
            if ($item['product_id'] == $productId) {
                unset($cart[$i]);
                break;
            }
        }

        $cart = array_values($cart);
        session([$cartKey => $cart]);

        return redirect()->route('order.cart', [$tenantIdentifier, $tablenumber]);
    }


    // Proses checkout
    // public function checkout(Request $request, $tablenumber)
    // {
    //     // DB::beginTransaction();
    //     try {
    //         $cart = session('cart_' . $tablenumber, []);
    //         if (empty($cart)) {
    //             return back()->withErrors('Keranjang kosong, silakan tambahkan menu terlebih dahulu.');
    //         }

    //         $table = Table::where('name', $tablenumber)->firstOrFail();

    //         $order = Order::create([
    //             'table_id' => $table->id,
    //             // code format : yymmdd-{incremental 4 chars by today}
    //             'code' => now()->format('ymd-') . Str::padLeft(Order::whereDate('created_at', now())->count() + 1, 4, '0'),
    //             'total_amount' => collect($cart)->sum(fn($i) => $i['price'] * $i['qty']),
    //             'status' => 'pending',
    //             'placed_at' => now(),
    //             'payment_method' => 'qris',
    //             'notes' => $request->input('notes', ''),
    //             'customer_name' => $request->input('customer_name', null),
    //             'customer_phone' => $request->input('customer_phone', null),
    //         ]);

    //         foreach ($cart as $item) {
    //             OrderItem::create([
    //                 'order_id' => $order->id,
    //                 'product_id' => $item['product_id'],
    //                 'quantity' => $item['qty'],
    //                 'price' => $item['price'],
    //                 'total' => $item['price'] * $item['qty'],
    //                 'note' => $item['note'] ?? null,
    //             ]);
    //         }
    //         session()->forget('cart_' . $tablenumber);
    //         // DB::commit();
    //         return redirect()->route('order.qris', [$tablenumber, $order->code]);
    //     } catch (\Exception $e) {
    //         DB::rollback();
    //         return back()->withErrors('Gagal checkout: ' . $e->getMessage());
    //     }
    // }

    public function checkout(Request $request, $tenantIdentifier, $tablenumber)
    {
        $tenant = $this->getTenantFromIdentifier($tenantIdentifier);
        
        Log::info('CHECKOUT: masuk ke method checkout', [
            'tenant' => $tenant->business_name,
            'table' => $tablenumber,
            'request' => $request->all()
        ]);

        // Pre-validate input
        $cart = session('cart_' . $tenantIdentifier . '_' . $tablenumber, []);
        if (empty($cart)) {
            return back()->withErrors('Keranjang kosong, silakan tambahkan menu terlebih dahulu.');
        }

        // Fast validation sebelum masuk transaction
        $preValidation = $this->validateCartStock($cart);
        if (!$preValidation['is_valid']) {
            return back()->withErrors('Cart validation failed: ' . implode(', ', $preValidation['errors']));
        }

        // Gunakan database transaction dengan pessimistic locking
        try {
            return DB::transaction(function () use ($request, $tenantIdentifier, $tablenumber, $cart, $tenant) {
                // Lock semua products yang ada di cart untuk mencegah race condition
                $productIds = collect($cart)->pluck('product_id')->unique();
                $products = Product::whereIn('id', $productIds)
                    ->lockForUpdate() // Pessimistic lock
                    ->get()
                    ->keyBy('id');

                // Final validation dengan data yang ter-lock
                foreach ($cart as $item) {
                    $product = $products->get($item['product_id']);
                    if (!$product) {
                        throw new \Exception("Product with ID {$item['product_id']} not found");
                    }
                    
                    // Validasi status dan stock produk dengan data terkini
                    if (!$product->isAvailable()) {
                        throw new \Exception("Product '{$product->name}' is not available");
                    }
                    
                    if ($product->stock < $item['qty']) {
                        throw new \Exception("Insufficient stock for product '{$product->name}'. Available: {$product->stock}, Requested: {$item['qty']}");
                    }
                }

                $table = Table::withoutGlobalScope('tenant')
                    ->where('tenant_id', $tenant->id)
                    ->where('name', $tablenumber)
                    ->firstOrFail();
                $paymentMethod = $request->input('payment_method', 'qris');

                // Fetch default Tax & Service if not provided (Auto-apply logic)
                $taxId = $request->input('tax_id');
                $serviceId = $request->input('service_id');

                if (!$taxId) {
                    $taxIds = json_decode(\App\Models\Setting::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->where('key', 'selected_tax_ids')->value('value') ?? '[]', true);
                    if (!empty($taxIds)) {
                        // Get the first active tax
                        $taxId = \App\Models\Tax::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->where('status', 'active')->where('type', 'pajak')->whereIn('id', $taxIds)->value('id');
                    }
                }

                if (!$serviceId) {
                    $serviceIds = json_decode(\App\Models\Setting::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->where('key', 'selected_service_ids')->value('value') ?? '[]', true);
                    if (!empty($serviceIds)) {
                        // Get the first active service charge
                        $serviceId = \App\Models\Tax::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->where('status', 'active')->where('type', 'layanan')->whereIn('id', $serviceIds)->value('id');
                    }
                }

                // Calculate totals with discount, tax, and service charge
                $totals = $this->calculateOrderTotals(
                    $cart, 
                    $request->input('discount_id'),
                    $taxId,
                    $serviceId
                );
                
                Log::info('CHECKOUT: Calculated totals', $totals);
                
                // Create order with all calculations
                $order = Order::create([
                    'table_id' => $table->id,
                    'tenant_id' => $table->tenant_id, // ← EXPLICIT tenant isolation!
                    'code' => 'JG-' . now()->format('ymd-') . Str::padLeft(Order::withoutGlobalScope('tenant')->whereDate('created_at', now())->count() + 1, 4, '0'),
                    'subtotal' => $totals['subtotal'],
                    'discount_id' => $totals['discount_id'],
                    'discount_amount' => $totals['discount_amount'],
                    'tax_percentage' => $totals['tax_percentage'],
                    'tax_amount' => $totals['tax_amount'],
                    'service_charge_percentage' => $totals['service_charge_percentage'],
                    'service_charge_amount' => $totals['service_charge_amount'],
                    'total_amount' => $totals['total_amount'],
                    'status' => 'pending', // Changed: was 'paid', now 'pending' to wait for payment
                    'placed_at' => now(),
                    'payment_method' => $paymentMethod,
                    'notes' => $request->input('notes', ''),
                    'customer_name' => $request->input('customer_name', null),
                    'customer_phone' => $request->input('customer_phone', null),
                    'customer_email' => $request->input('customer_email', null),
                ]);

                Log::info('CHECKOUT: order berhasil dibuat', [
                    'order_id' => $order->id,
                    'order_code' => $order->code
                ]);
                
                // 🔥 UPDATE TABLE STATUS to occupied and set customer info
                $table->status = 'occupied';
                $table->customer_name = $request->input('customer_name', null);
                $table->customer_phone = $request->input('customer_phone', null);
                $table->occupied_at = now();
                $table->save();
                Log::info('CHECKOUT: Table status and customer info updated', [
                    'table_id' => $table->id,
                    'table_name' => $table->name,
                    'customer_name' => $table->customer_name,
                    'customer_phone' => $table->customer_phone
                ]);

                // Reserve stock untuk semua payment methods (kecuali cash langsung complete)
                foreach ($cart as $item) {
                    $product = $products->get($item['product_id']);
                    
                    // Final check sebelum reserve stock
                    if ($product->stock < $item['qty']) {
                        throw new \Exception("Stock insufficient for {$product->name}. Available: {$product->stock}, Requested: {$item['qty']}");
                    }
                    
                    // Reserve stock dengan mengurangi sekaligus
                    $product->decrement('stock', $item['qty']);
                    
                    Log::info('CHECKOUT: Stock reserved', [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'quantity_reserved' => $item['qty'],
                        'remaining_stock' => $product->fresh()->stock
                    ]);

                    // Create order item
                    OrderItem::create([
                        'tenant_id' => $table->tenant_id, // CRITICAL: For tenant isolation
                        'order_id' => $order->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['qty'],
                        'price' => $item['price'],
                        'total' => $item['price'] * $item['qty'],
                        'notes' => $item['note'] ?? null,
                    ]);
                }

                // Clear cart after successful order creation
                session()->forget('cart_' . $tenantIdentifier . '_' . $tablenumber);

                // Handle payment methods
                return $this->processPayment($order, $cart, $paymentMethod, $tablenumber, $totals, $tenantIdentifier);

            }, 5); // Retry 5 kali jika deadlock
            
        } catch (\Exception $e) {
            Log::error('CHECKOUT: Transaction failed', [
                'table' => $tablenumber,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->withErrors('Checkout failed: ' . $e->getMessage());
        }
    }

    /**
     * Process payment berdasarkan payment method
     */
    private function processPayment($order, $cart, $paymentMethod, $tablenumber, $totals, $tenantIdentifier = null)
    {
        // Generate tenant identifier from order if not provided
        if (!$tenantIdentifier) {
            $tenant = $order->table->tenant;
            $tenantIdentifier = "{$tenant->slug}-{$tenant->short_uuid}";
        }
        // Build item details untuk Midtrans
        $itemDetails = collect($cart)->map(function ($i) {
            return [
                "id"       => $i['product_id'],
                "price"    => $i['price'],
                "quantity" => $i['qty'],
                "name"     => $i['name'] ?? 'Menu',
            ];
        })->toArray();
        
        // Add discount as negative item (if exists)
        if ($totals['discount_amount'] > 0) {
            $itemDetails[] = [
                "id" => "discount",
                "price" => -1 * $totals['discount_amount'],
                "quantity" => 1,
                "name" => "Discount"
            ];
        }
        
        // Add tax (if exists)
        if ($totals['tax_amount'] > 0) {
            $itemDetails[] = [
                "id" => "tax",
                "price" => $totals['tax_amount'],
                "quantity" => 1,
                "name" => "Tax ({$totals['tax_percentage']}%)"
            ];
        }
        
        // Add service charge (if exists)
        if ($totals['service_charge_amount'] > 0) {
            $itemDetails[] = [
                "id" => "service",
                "price" => $totals['service_charge_amount'],
                "quantity" => 1,
                "name" => "Service Charge ({$totals['service_charge_percentage']}%)"
            ];
        }
        
        $params = [
            "transaction_details" => [
                "order_id"      => $order->code,
                "gross_amount"  => (int) $order->total_amount,
            ],
            "item_details" => $itemDetails,
            "customer_details" => [
                "first_name" => $order->customer_name ?? 'Guest',
                "email"      => $order->customer_email ?? 'guest@example.com',
                "phone"      => $order->customer_phone,
            ]
        ];

        switch ($paymentMethod) {
            case 'qris':
                return $this->processQrisPayment($order, $params, $tablenumber, $tenantIdentifier);
            
            case 'gopay':
                return $this->processGopayPayment($order, $params, $tablenumber, $tenantIdentifier);
            
            case 'cash':
                // For cash, order is immediately paid (will be tracked: paid → cooking → complete)
                $order->status = 'paid'; // Changed: was 'completed', now 'paid' for order tracking
                $order->payment_status = 'paid'; // Sync payment_status
                $order->completed_at = now();
                $order->save();
                return redirect()->route('order.success', [$tenantIdentifier, $tablenumber, $order->code]);
            
            default:
                return redirect()->route('order.success', [$tenantIdentifier, $tablenumber, $order->code]);
        }
    }

    private function processQrisPayment($order, $params, $tablenumber, $tenantIdentifier = null)
    {
        try {
            // CRITICAL: Set Midtrans configuration from tenant settings
            // Get tenant_id from order's table
            $table = $order->table;
            $tenant = $table->tenant;
            
            // Generate tenant identifier if not provided
            if (!$tenantIdentifier) {
                $tenantIdentifier = "{$tenant->slug}-{$tenant->short_uuid}";
            }
            
            $this->configureMidtrans($table->tenant_id);
            
            $params["payment_type"] = "qris";
            Log::info('CHECKOUT: memanggil CoreApi::charge', $params);

            try {
                $qris = CoreApi::charge($params);
                $order->payment_url = $qris->actions[0]->url ?? null;
                $order->qr_string = $qris->qr_string ?? null;
            } catch (\Exception $e) {
                Log::error('MIDTRANS API ERROR: ' . $e->getMessage());
                // Throw exception to let the user know something is wrong
                throw $e;
            }

            $order->save();

            Log::info('CHECKOUT: QRIS berhasil dibuat (atau Mock)', [
                'order_id' => $order->id,
                'qr_string' => $order->qr_string,
                'payment_url' => $order->payment_url
            ]);

            // Send webhook notification (Mock doesn't trigger real webhook, but we can simulate if needed)
            // $this->sendWebhookNotification($order);

            return redirect()->route('order.qris', [$tenantIdentifier, $tablenumber, $order->code]);
            
        } catch (\Exception $e) {
            // This catch block now handles only non-API errors (like DB issues)
            // or if the fallback itself fails
            
            // Cancel order if payment fails
            $order->status = 'cancelled';
            $order->save();
            
            // CRITICAL: Restore stock that was reserved
            // Load items relationship first (in case not loaded)
            $order->load('orderItems');
            
            if ($order->orderItems && $order->orderItems->count() > 0) {
                foreach ($order->orderItems as $item) {
                    $product = Product::find($item->product_id);
                    if ($product) {
                        $product->increment('stock', $item->quantity);
                        Log::info('QRIS: Stock restored after payment failure', [
                            'product_id' => $product->id,
                            'quantity_restored' => $item->quantity,
                            'new_stock' => $product->fresh()->stock
                        ]);
                    }
                }
            }
            
            Log::error('QRIS Payment processing failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('order.cart', [$tenantIdentifier, $tablenumber])
                ->withErrors('Payment processing failed: ' . $e->getMessage());
        }
    }

    private function processGopayPayment($order, $params, $tablenumber, $tenantIdentifier = null)
    {
        // CRITICAL: Set Midtrans configuration from tenant settings
        $this->configureMidtrans();
        
        $params["payment_type"] = "gopay";
        $params["gopay"] = [
            "enable_callback" => true,
            "callback_url" => route('order.success', [$tenantIdentifier, $tablenumber, $order->code])
        ];

        Log::info('CHECKOUT: memanggil CoreApi::charge [GoPay]', $params);
        $gopay = CoreApi::charge($params);

        $qrCodeUrl = collect($gopay->actions)->firstWhere('name', 'generate-qr-code')->url ?? null;
        $deeplinkUrl = collect($gopay->actions)->firstWhere('name', 'deeplink-redirect')->url ?? null;

        $order->qr_string = null;
        $order->payment_url = $qrCodeUrl ?? $deeplinkUrl;
        $order->save();

        Log::info('CHECKOUT: GoPay berhasil dibuat', [
            'order_id' => $order->id,
            'qr_code_url' => $qrCodeUrl,
            'deeplink_url' => $deeplinkUrl
        ]);

        return redirect()->route('order.qris', [$tenantIdentifier, $tablenumber, $order->code]);
    }

    private function configureN8n($tenantId)
    {
        $webhookUrl = \App\Models\Setting::where('tenant_id', $tenantId)
            ->where('key', 'n8n_webhook_url')
            ->value('value');

        if (!$webhookUrl) {
            // Optional: Log warning but don't throw if N8N is optional
            // throw new \Exception('N8N Webhook URL not configured for this tenant.');
            return null;
        }

        return $webhookUrl;
    }

    private function sendWebhookNotification($order)
    {
        try {
            // Configure N8N for this tenant
            $webhookUrl = $this->configureN8n($order->tenant_id);
            
            if (!$webhookUrl) {
                Log::info('N8N Webhook skipped: Not configured for tenant ' . $order->tenant_id);
                return;
            }

            Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($webhookUrl, [
                'order_id'   => $order->id,
                'order_code' => $order->code,
                'table_id'   => $order->table_id,
                'total'      => number_format($order->total_amount, 0, ',', '.'),
                'phone' => preg_replace('/^0/', '62', $order->customer_phone),
            ]);
            
            Log::info('N8N Webhook sent successfully', ['order_code' => $order->code]);
            
        } catch (\Exception $e) {
            Log::error('Gagal kirim ke webhook n8n: ' . $e->getMessage());
        }
    }
    // Halaman QRIS (dummy)
    public function qris($tenantIdentifier, $tablenumber, $code)
    {
        $tenant = $this->getTenantFromIdentifier($tenantIdentifier);
        
        // Public route - bypass tenant scope
        $order = Order::withoutGlobalScope('tenant')->where('code', $code)->firstOrFail();
        $table = Table::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->where('name', $tablenumber)
            ->firstOrFail();
        
        // Add tenant identifier for views
        $table->tenantIdentifier = $tenantIdentifier;
        
        // Fetch Midtrans Environment Setting
        $isProduction = (bool) \App\Models\Setting::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->where('key', 'midtrans_is_production')
            ->value('value');
        
        return view('order.qris', compact('order', 'table', 'isProduction'));
    }

    // Konfirmasi QRIS (dummy)
    // public function qrisConfirm(Request $request, $tablenumber, $code)
    // {
    //     $order = Order::where('code', $code)->firstOrFail();
    //     $order->status = 'completed';
    //     $order->completed_at = now();
    //     $order->payment_method = 'qris';
    //     $order->save();
    //     return redirect()->route('order.success', [$tablenumber, $code]);
    // }

    // public function qrisConfirm(Request $request, $tablenumber, $code)
    // {
    //     $order = Order::where('code', $code)->firstOrFail();

    //     try {
    //         $status = Transaction::status($order->code);
    //         if ($status->transaction_status == 'settlement') {
    //             $order->status = 'completed';
    //             $order->completed_at = now();
    //             $order->save();
    //         } else {
    //             return back()->withErrors('Pembayaran belum selesai, status: ' . $status->transaction_status);
    //         }
    //     } catch (\Exception $e) {
    //         return back()->withErrors('Gagal cek status: ' . $e->getMessage());
    //     }

    //     return redirect()->route('order.success', [$tablenumber, $code]);
    // }

    public function qrisConfirm(Request $request, $tenantIdentifier, $tablenumber, $code)
    {
        $tenant = $this->getTenantFromIdentifier($tenantIdentifier);
        
        // Public route - bypass tenant scope
        $order = Order::withoutGlobalScope('tenant')->where('code', $code)->firstOrFail();

        try {
            // Cek status transaksi di Midtrans
            $status = \Midtrans\Transaction::status($order->code);

            // Log status untuk debug
            Log::info('QRIS/GoPay CONFIRM: Status Midtrans', [
                'order_code' => $order->code,
                'payment_method' => $order->payment_method,
                'midtrans_status' => $status->transaction_status ?? 'unknown'
            ]);
            
            // Jika transaksi sudah berhasil (settlement / capture)
            if (in_array($status->transaction_status, ['settlement', 'capture'])) {
                $order->status = 'paid';
                $order->payment_status = 'paid'; // Sync payment_status
                $order->payment_amount = $status->gross_amount; // Save payment amount
                $order->completed_at = now();
                $order->save();
                
                // STOCK SUDAH DIKURANGI SAAT CHECKOUT - TIDAK PERLU KURANGI LAGI
                Log::info('QRIS/GoPay CONFIRM: Payment confirmed - stock already reserved at checkout', [
                    'order_code' => $order->code,
                    'status' => $order->status,
                    'note' => 'Stock was already reduced during checkout process'
                ]);
                
                // Kirim notifikasi ke user
                $this->sendNotification('1 New Order', 'New order received from table ' . $order->table->name, $order->tenant_id);
                
                return redirect()->route('order.success', [$tenantIdentifier, $tablenumber, $code]);
            }

            // Jika transaksi masih pending
            if ($status->transaction_status == 'pending') {
                return back()->withErrors('Pembayaran masih pending, silakan tunggu atau coba lagi.');
            }

            // Jika transaksi gagal / expire / cancel
            if (in_array($status->transaction_status, ['expire', 'cancel', 'deny'])) {
                $order->status = 'failed';
                $order->save();

                // Return stock karena payment gagal
                try {
                    $this->releaseStock($order);
                    Log::info('QRIS/GoPay CONFIRM: Stock released due to failed payment', [
                        'order_code' => $order->code,
                        'status' => $status->transaction_status
                    ]);
                } catch (\Exception $e) {
                    Log::error('QRIS/GoPay CONFIRM: Failed to release stock', [
                        'order_code' => $order->code,
                        'error' => $e->getMessage()
                    ]);
                }

                return back()->withErrors('Pembayaran gagal atau kadaluarsa. Stock telah dikembalikan. Silakan lakukan pemesanan ulang.');
            }

            // Jika ada status lain yang tidak dikenali
            return back()->withErrors('Status pembayaran: ' . $status->transaction_status);
        } catch (\Exception $e) {
            Log::error('QRIS/GoPay CONFIRM ERROR', [
                'order_code' => $order->code,
                'message' => $e->getMessage()
            ]);

            return back()->withErrors('Gagal cek status: ' . $e->getMessage());
        }
    }


    // Sukses
    public function success($tenantIdentifier, $tablenumber, $code)
    {
        $tenant = $this->getTenantFromIdentifier($tenantIdentifier);
        
        // Public route - bypass tenant scope
        $order = Order::withoutGlobalScope('tenant')->where('code', $code)->firstOrFail();
        $table = Table::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->where('name', $tablenumber)
            ->firstOrFail();
        
        // Add tenant identifier for views
        $table->tenantIdentifier = $tenantIdentifier;
        
        return view('order.success', compact('order', 'table'));
    }

    public function midtransCallback(Request $request)
    {
        // 1. Get Order ID from request to identify Tenant
        $orderId = $request->input('order_id');
        
        // Public callback - bypass tenant scope
        $order = Order::withoutGlobalScope('tenant')->where('code', $orderId)->first();
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        try {
            // 2. Configure Midtrans for this Tenant
            $this->configureMidtrans($order->tenant_id);
            
            // 3. Process Notification
            $notif = new Notification();
            $transaction = $notif->transaction_status;
            $type = $notif->payment_type;
            $fraud = $notif->fraud_status;

            if ($transaction == 'capture') {
                if ($fraud == 'challenge') {
                    // TODO: Handle challenge
                } else {
                    $order->status = 'paid';
                    $order->payment_status = 'paid'; // Sync payment_status
                    $order->payment_amount = $notif->gross_amount; // Save payment amount
                    $order->completed_at = now();
                    $this->decreaseProductStock($order);
                    $this->sendNotification('1 New Order', 'New order received from table ' . $order->table->name, $order->tenant_id);
                }
            } elseif ($transaction == 'settlement') {
                $order->status = 'paid';
                $order->payment_status = 'paid'; // Sync payment_status
                $order->payment_amount = $notif->gross_amount; // Save payment amount
                $order->completed_at = now();
                $this->decreaseProductStock($order);
                $this->sendNotification('1 New Order', 'New order received from table ' . $order->table->name, $order->tenant_id);
            } elseif ($transaction == 'pending') {
                $order->status = 'pending';
            } elseif ($transaction == 'deny' || $transaction == 'expire' || $transaction == 'cancel') {
                $order->status = 'failed';
            }
            
            $order->save();
            return response()->json(['message' => 'OK']);
            
        } catch (\Exception $e) {
            Log::error('Midtrans Callback Error: ' . $e->getMessage());
            return response()->json(['message' => 'Error processing callback'], 500);
        }
    }

    private function configureFirebase($tenantId)
    {
        $firebaseConfig = \App\Models\Setting::where('tenant_id', $tenantId)
            ->where('key', 'firebase_credentials')
            ->value('value');

        if (!$firebaseConfig) {
            Log::warning("Firebase credentials not found for tenant {$tenantId}");
            return null;
        }

        try {
            // Initialize Firebase with tenant-specific credentials
            // Assuming the value is a JSON string or path to JSON file
            // If it's a JSON string, we might need to save it to a temp file or use a factory that accepts JSON string
            
            // For simplicity, assuming we use the Factory directly
            $factory = (new \Kreait\Firebase\Factory)
                ->withServiceAccount(json_decode($firebaseConfig, true));
                
            return $factory->createMessaging();
            
        } catch (\Exception $e) {
            Log::error("Failed to configure Firebase for tenant {$tenantId}: " . $e->getMessage());
            return null;
        }
    }

    // Method for send notification to restaurant/user/driver
    public function sendNotification($title, $message, $tenantId = null)
    {
        // If tenantId is not provided, try to get from auth user
        if (!$tenantId && auth()->check()) {
            $tenantId = auth()->user()->tenant_id;
        }
        
        if (!$tenantId) {
            Log::warning('Cannot send notification: Tenant ID not identified.');
            return;
        }

        // Configure Firebase for this tenant
        $messaging = $this->configureFirebase($tenantId);
        
        if (!$messaging) {
            return;
        }

        // Find logged-in users FOR THIS TENANT only
        $users = User::where('tenant_id', $tenantId)
            ->where('is_login', true)
            ->whereNotNull('fcm_token')
            ->get();
            
        Log::info("Sending notification to tenant {$tenantId}", ['users_count' => $users->count()]);

        foreach ($users as $user) {
            try {
                $notification = FirebaseNotification::create($title, $message);
                $cloudMessage = CloudMessage::withTarget('token', $user->fcm_token)
                    ->withNotification($notification);

                $messaging->send($cloudMessage);
            } catch (\Exception $e) {
                Log::error('Failed to send notification to user ' . $user->id, ['error' => $e->getMessage()]);
            }
        }
    }

    public function detail($tenantIdentifier, $tablenumber, $productId)
    {
        $tenant = $this->getTenantFromIdentifier($tenantIdentifier);
        
        $table = Table::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->where('name', $tablenumber)
            ->firstOrFail();
            
        // Add tenant identifier for views
        $table->tenantIdentifier = $tenantIdentifier;
            
        $product = Product::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->findOrFail($productId);
            
        return view('order.product_detail', compact('table', 'product'));
    }

    // Tambah ke keranjang (session) dari detail page
    public function addToCartWithNote(Request $request, $tenantIdentifier, $tablenumber, $productId)
    {
        try {
            $tenant = $this->getTenantFromIdentifier($tenantIdentifier);
            
            $table = Table::withoutGlobalScope('tenant')
                ->where('tenant_id', $tenant->id)
                ->where('name', $tablenumber)
                ->firstOrFail();
                
            $product = Product::withoutGlobalScope('tenant')
                ->where('tenant_id', $tenant->id)
                ->findOrFail($productId);
            $qtyToAdd = (int) $request->input('qty', 1);

            // Validasi stock real-time dengan locking
            $errors = $this->validateStockAvailability([
                ['product_id' => $product->id, 'quantity' => $qtyToAdd]
            ]);

            if (!empty($errors)) {
                return redirect()
                    ->route('order.detail', [$tenantIdentifier, $tablenumber, $product->id])
                    ->with('error', $errors[0]);
            }

            $cartKey = 'cart_' . $tenantIdentifier . '_' . $tablenumber;
            $cart = session()->get($cartKey, []);

            // Hitung total qty yang sudah ada di cart untuk produk ini
            $currentQtyInCart = 0;
            foreach ($cart as $item) {
                if ($item['product_id'] == $product->id) {
                    $currentQtyInCart += $item['qty'];
                }
            }

            $newTotalQty = $currentQtyInCart + $qtyToAdd;

            // Double check total qty dengan stock terkini
            $finalErrors = $this->validateStockAvailability([
                ['product_id' => $product->id, 'quantity' => $newTotalQty]
            ]);

            if (!empty($finalErrors)) {
                return redirect()
                    ->route('order.detail', [$tenantIdentifier, $tablenumber, $product->id])
                    ->with('error', "Cannot add {$qtyToAdd} items. " . $finalErrors[0]);
            }

            $cart[] = [
                'product_id' => $product->id,
                'name'       => $product->name,
                'price'      => $product->price,
                'qty'        => $qtyToAdd,
                'note'       => $request->input('notes', '')
            ];

            session()->put($cartKey, $cart);

            return redirect()
                ->route('order.detail', [$tenantIdentifier, $tablenumber, $product->id])
                ->with('success', "Added {$qtyToAdd} {$product->name} to cart");

        } catch (\Exception $e) {
            Log::error('Error adding to cart with note', [
                'product_id' => $productId,
                'table' => $tablenumber,
                'error' => $e->getMessage()
            ]);

            return redirect()
                ->route('order.detail', [$tenantIdentifier, $tablenumber, $productId])
                ->with('error', 'Failed to add item to cart. Please try again.');
        }
    }


    /**
     * Calculate order totals with discount, tax, and service charge
     */
    private function calculateOrderTotals($cart, $discountId = null, $taxId = null, $serviceId = null)
    {
        $subtotal = collect($cart)->sum(fn($item) => $item['price'] * $item['qty']);
        
        // 1. Calculate Discount
        $discountAmount = 0;
        $discount = null;
        
        if ($discountId) {
            $discount = \App\Models\Discount::withoutGlobalScope('tenant')->find($discountId);
            if ($discount) {
                if ($discount->type === 'percentage') {
                    $discountAmount = $subtotal * ($discount->value / 100);
                } else {
                    $discountAmount = min($discount->value, $subtotal);
                }
            }
        }
        $discountAmount = round($discountAmount);
        
        // Subtotal after discount
        $subtotalAfterDiscount = max(0, $subtotal - $discountAmount);
        
        // 2. Calculate Tax (Auto-select if not provided but active in settings)
        $taxAmount = 0;
        $taxPercentage = 0;
        
        if ($taxId) {
            $tax = \App\Models\Tax::withoutGlobalScope('tenant')->find($taxId);
            if ($tax) {
                $taxPercentage = $tax->value;
                $taxAmount = $subtotalAfterDiscount * ($taxPercentage / 100);
            }
        }
        $taxAmount = round($taxAmount);
        
        // 3. Calculate Service Charge
        $serviceChargeAmount = 0;
        $serviceChargePercentage = 0;
        
        if ($serviceId) {
            $service = \App\Models\Tax::withoutGlobalScope('tenant')->find($serviceId);
            if ($service) {
                $serviceChargePercentage = $service->value;
                $serviceChargeAmount = $subtotalAfterDiscount * ($serviceChargePercentage / 100);
            }
        }
        $serviceChargeAmount = round($serviceChargeAmount);
        
        // 4. Final Total
        $totalAmount = $subtotalAfterDiscount + $taxAmount + $serviceChargeAmount;
        
        return [
            'subtotal' => $subtotal,
            'discount_id' => $discount ? $discount->id : null,
            'discount_amount' => $discountAmount,
            'tax_percentage' => $taxPercentage,
            'tax_amount' => $taxAmount,
            'service_charge_percentage' => $serviceChargePercentage,
            'service_charge_amount' => $serviceChargeAmount,
            'total_amount' => round($totalAmount)
        ];
    }

    /**
     * Mengurangi stock produk berdasarkan order items
     */
    private function decreaseProductStock(Order $order)
    {
        Log::info('Starting stock decrease process', [
            'order_id' => $order->id,
            'order_items_count' => $order->orderItems->count(),
        ]);
        
        try {
            foreach ($order->orderItems as $orderItem) {
                $product = $orderItem->product;
                
                Log::info('Processing order item for stock decrease', [
                    'order_id' => $order->id,
                    'order_item_id' => $orderItem->id,
                    'product_id' => $orderItem->product_id,
                    'quantity' => $orderItem->quantity,
                    'product_found' => $product ? 'yes' : 'no',
                ]);
                
                if ($product) {
                    $currentStock = $product->stock;
                    Log::info('Before stock decrease', [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'current_stock' => $currentStock,
                        'quantity_to_decrease' => $orderItem->quantity,
                    ]);
                    
                    $result = $product->decreaseStock($orderItem->quantity);
                    
                    if (!$result) {
                        Log::warning('Failed to decrease stock', [
                            'order_id' => $order->id,
                            'product_id' => $product->id,
                            'product_name' => $product->name,
                            'requested_qty' => $orderItem->quantity,
                            'available_stock' => $product->stock,
                        ]);
                    } else {
                        Log::info('Stock decreased successfully', [
                            'order_id' => $order->id,
                            'product_id' => $product->id,
                            'product_name' => $product->name,
                            'decreased_qty' => $orderItem->quantity,
                            'remaining_stock' => $product->fresh()->stock,
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Error decreasing product stock', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * SHARED LOGIC: Sync Order Status with Midtrans
     * Used by both Web (checkPaymentStatus) and App (checkOrderStatus)
     * Ensures identical behavior for both platforms.
     */
    private function syncOrderWithMidtrans(Order $order)
    {
        try {
            // 1. Configure Midtrans for this Tenant
            $this->configureMidtrans($order->tenant_id);

            // 2. Check Status from Midtrans
            Log::info("MIDTRANS SYNC: Checking status for {$order->code}");
            $midtransStatus = \Midtrans\Transaction::status($order->code);
            $transactionStatus = $midtransStatus->transaction_status;
            $fraudStatus = $midtransStatus->fraud_status;

            Log::info("MIDTRANS SYNC: Response", [
                'order_code' => $order->code,
                'transaction_status' => $transactionStatus,
                'fraud_status' => $fraudStatus
            ]);

            // 3. Update Order based on Midtrans Status
            if ($transactionStatus == 'capture') {
                if ($fraudStatus == 'challenge') {
                    // TODO: Handle challenge
                } else if ($fraudStatus == 'accept') {
                    $this->markOrderAsPaid($order);
                }
            } else if ($transactionStatus == 'settlement') {
                $this->markOrderAsPaid($order);
            } else if ($transactionStatus == 'cancel' || $transactionStatus == 'deny' || $transactionStatus == 'expire') {
                $order->status = 'failed';
                $order->save();
            } else if ($transactionStatus == 'pending') {
                // Do nothing, keep pending
            }

            return $order->fresh(); // Return updated order

        } catch (\Exception $e) {
            Log::error("MIDTRANS SYNC ERROR: " . $e->getMessage());
            // Don't throw, just return original order
            return $order;
        }
    }

    /**
     * Helper to mark order as paid and update stock/notif
     */
    private function markOrderAsPaid(Order $order)
    {
        if ($order->status !== 'paid') {
            $order->status = 'paid';
            $order->payment_status = 'paid';
            $order->completed_at = now();
            $order->save();

            // Decrease stock
            $this->decreaseProductStock($order);
            
            // Send Notification
            $this->sendNotification('1 New Order', 'New order received from table ' . ($order->table->name ?? 'Unknown'), $order->tenant_id);
            
            Log::info("MIDTRANS SYNC: Order {$order->code} marked as PAID");
        }
    }

    /**
     * AJAX endpoint untuk check payment status (WEB)
     */
    public function checkPaymentStatus(Request $request, $tenantIdentifier, $tablenumber, $code)
    {
        try {
            $tenant = $this->getTenantFromIdentifier($tenantIdentifier);
            $order = Order::withoutGlobalScope('tenant')->where('code', $code)->firstOrFail();

            // Sync with Midtrans if pending
            if ($order->status === 'pending') {
                $order = $this->syncOrderWithMidtrans($order);
            }

            if ($order->status === 'paid') {
                return response()->json([
                    'status' => 'paid',
                    'message' => 'Payment completed successfully',
                    'redirect_url' => route('order.success', [$tenantIdentifier, $tablenumber, $code])
                ]);
            }

            if ($order->status === 'failed') {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Payment failed or expired',
                    'redirect_url' => route('order.menu', $tablenumber)
                ]);
            }
            
            return response()->json([
                'status' => 'pending',
                'message' => 'Payment is still pending',
                'order_status' => $order->status
            ]);
            
        } catch (\Exception $e) {
            Log::error('AJAX Payment Status Check Error', ['order_code' => $code, 'error' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => 'Failed to check payment status'], 500);
        }
    }

    /**
     * API: Check Order Status (APP)
     */
    public function checkOrderStatus($orderCode)
    {
        try {
            // Find order by Code OR ID
            $order = Order::withoutGlobalScope('tenant')
                ->where(function($query) use ($orderCode) {
                    $query->where('code', $orderCode)
                          ->orWhere('id', $orderCode);
                })
                ->firstOrFail();
            
            Log::info("API CHECK STATUS: Order found", ['code' => $order->code, 'status' => $order->status]);

            // Sync with Midtrans if pending
            if ($order->status === 'pending') {
                $order = $this->syncOrderWithMidtrans($order);
            }

            // Determine transaction_status for App compatibility
            $transactionStatus = 'pending';
            if ($order->status === 'paid') {
                $transactionStatus = 'settlement';
            } else if ($order->status === 'failed' || $order->status === 'cancelled') {
                $transactionStatus = 'expire';
            }

            return response()->json([
                'success' => true,
                'status' => $order->status,
                'payment_status' => $order->status,
                'transaction_status' => $transactionStatus, // CRITICAL for Flutter App
                'order_id' => $order->id,
                'order_code' => $order->code,
                'total_amount' => $order->total_amount,
            ]);

        } catch (\Exception $e) {
            Log::error("API CHECK STATUS ERROR", ['order_code' => $orderCode, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to check order status: ' . $e->getMessage()
            ], 500);
        }
    }
    /**
     * API: Create QRIS Order (for Flutter App)
     */
    public function createQrisOrder(Request $request)
    {
        try {
            // Validate input
            $request->validate([
                'tenant_id' => 'required',
                'table_number' => 'required',
                'cart_items' => 'required|array',
                'customer_name' => 'required',
            ]);

            $tenantId = $request->tenant_id;
            $tableNumber = $request->table_number;
            $cartItems = $request->cart_items;
            $customerName = $request->customer_name;
            $originalTableNumber = $tableNumber; // Capture original input

            // Calculate Subtotal
            $subTotal = collect($cartItems)->sum(fn($item) => $item['price'] * $item['qty']);
            
            // --- SECURE CALCULATION START ---
            // Fetch default Tax & Service settings for the tenant
            $taxId = null;
            $serviceId = null;

            // Get selected tax IDs from settings
            $taxIds = json_decode(\App\Models\Setting::withoutGlobalScope('tenant')
                ->where('tenant_id', $tenantId)
                ->where('key', 'selected_tax_ids')
                ->value('value') ?? '[]', true);
                
            if (!empty($taxIds)) {
                $taxId = \App\Models\Tax::withoutGlobalScope('tenant')
                    ->where('tenant_id', $tenantId)
                    ->where('status', 'active')
                    ->where('type', 'pajak')
                    ->whereIn('id', $taxIds)
                    ->value('id');
            }

            // Get selected service IDs from settings
            $serviceIds = json_decode(\App\Models\Setting::withoutGlobalScope('tenant')
                ->where('tenant_id', $tenantId)
                ->where('key', 'selected_service_ids')
                ->value('value') ?? '[]', true);
                
            if (!empty($serviceIds)) {
                $serviceId = \App\Models\Tax::withoutGlobalScope('tenant')
                    ->where('tenant_id', $tenantId)
                    ->where('status', 'active')
                    ->where('type', 'layanan')
                    ->whereIn('id', $serviceIds)
                    ->value('id');
            }

            // Calculate totals using the shared helper method
            // Note: createQrisOrder doesn't support discount_id yet, so we pass null or handle it if needed
            $totals = $this->calculateOrderTotals(
                $cartItems, 
                null, // discount_id
                $taxId,
                $serviceId
            );

            $taxAmount = $totals['tax_amount'];
            $serviceChargeAmount = $totals['service_charge_amount'];
            $discountAmount = $totals['discount_amount'];
            $totalAmount = $totals['total_amount'];
            // --- SECURE CALCULATION END ---
            
            // Determine Order Type
            $orderType = $request->order_type ?? 'dine_in';
            
            // Gunakan database transaction
            $order = DB::transaction(function () use ($request, $tenantId, $tableNumber, $cartItems, $customerName, $originalTableNumber, $subTotal, $taxAmount, $serviceChargeAmount, $discountAmount, $totalAmount, $orderType) {
                
                // Handle default/missing table (Flutter sends "0" for Takeaway)
                if ($tableNumber === '0' || empty($tableNumber)) {
                    // Find or Create "Takeaway" table
                    $table = Table::withoutGlobalScope('tenant')
                        ->where('tenant_id', $tenantId)
                        ->where('name', 'Takeaway')
                        ->first();
                    
                    if (!$table) {
                        $table = Table::create([
                            'tenant_id' => $tenantId,
                            'name' => 'Takeaway',
                            'status' => 'available', // Takeaway table is always available
                            'location' => 'counter'
                        ]);
                    }
                } else {
                    // Get Specific Table with TENANT ISOLATION
                    $table = Table::withoutGlobalScope('tenant')
                        ->where('tenant_id', $tenantId)
                        ->where('name', $tableNumber)
                        ->first();
                        
                    // Fallback: If specific table not found, get ANY table (to prevent crash) but still scoped to tenant
                    if (!$table) {
                        $table = Table::withoutGlobalScope('tenant')
                            ->where('tenant_id', $tenantId)
                            ->firstOrFail();
                    }
                }
                
                // Create Order
                $order = Order::create([
                    'table_id' => $table->id,
                    'tenant_id' => $tenantId,
                    'code' => 'JG-' . now()->format('ymd-') . Str::padLeft(Order::withoutGlobalScope('tenant')->whereDate('created_at', now())->count() + 1, 4, '0'),
                    'total_amount' => $totalAmount,
                    'status' => 'pending',
                    'placed_at' => now(),
                    'payment_method' => 'qris',
                    'customer_name' => $customerName,
                    'notes' => $request->notes, // Save notes
                    'cashier_name' => $request->cashier_name, // Save cashier name
                    'order_type' => $orderType, // Save correct order type
                    
                    // Save Financial Details
                    'subtotal' => $subTotal, 
                    'discount_id' => null,
                    'discount_amount' => $discountAmount,
                    'tax_percentage' => ($subTotal > 0) ? ($taxAmount / $subTotal) * 100 : 0,
                    'tax_amount' => $taxAmount,
                    'service_charge_percentage' => ($subTotal > 0) ? ($serviceChargeAmount / $subTotal) * 100 : 0,
                    'service_charge_amount' => $serviceChargeAmount,
                ]);

                // Lock products for stock validation
                $productIds = collect($cartItems)->pluck('product_id');
                $products = Product::withoutGlobalScope('tenant')
                    ->whereIn('id', $productIds)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                // Create Order Items
                foreach ($cartItems as $item) {
                    OrderItem::create([
                        'tenant_id' => $tenantId,
                        'order_id' => $order->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['qty'],
                        'price' => $item['price'],
                        'total' => $item['price'] * $item['qty'],
                        'notes' => $item['note'] ?? null, // FIXED: Added item notes
                    ]);
                    
                    // Reserve Stock with validation
                    $product = $products->get($item['product_id']);
                    if ($product) {
                        if ($product->stock < $item['qty']) {
                            throw new \Exception("Insufficient stock for {$product->name}");
                        }
                        $product->decrement('stock', $item['qty']);
                    }
                }
                
                // Update Table Status ONLY if it's a real table (not Takeaway/0)
                if ($originalTableNumber !== '0') {
                    $table->status = 'occupied';
                    $table->customer_name = $customerName;
                    $table->occupied_at = now();
                    $table->save();
                }

                return $order;
            });

            // Configure Midtrans
            $this->configureMidtrans($tenantId);

            // Build Midtrans Item Details
            $itemDetails = collect($cartItems)->map(function($item) {
                return [
                    "id" => $item['product_id'],
                    "price" => $item['price'],
                    "quantity" => $item['qty'],
                    "name" => substr($item['name'], 0, 50) // Limit name length
                ];
            })->toArray();

            // Add Tax Item
            if ($taxAmount > 0) {
                $itemDetails[] = [
                    "id" => "TAX",
                    "price" => $taxAmount,
                    "quantity" => 1,
                    "name" => "Tax"
                ];
            }

            // Add Service Charge Item
            if ($serviceChargeAmount > 0) {
                $itemDetails[] = [
                    "id" => "SERVICE",
                    "price" => $serviceChargeAmount,
                    "quantity" => 1,
                    "name" => "Service Charge"
                ];
            }

            // Add Discount Item (Midtrans requires negative price for discount)
            if ($discountAmount > 0) {
                $itemDetails[] = [
                    "id" => "DISCOUNT",
                    "price" => -$discountAmount,
                    "quantity" => 1,
                    "name" => "Discount"
                ];
            }

            // Build Midtrans Params
            $params = [
                "payment_type" => "qris",
                "transaction_details" => [
                    "order_id" => $order->code,
                    "gross_amount" => (int) $totalAmount,
                ],
                "item_details" => $itemDetails,
                "customer_details" => [
                    "first_name" => $customerName,
                ],
                // NEW: Set Expiry time (e.g., 15 minutes)
                // This is better than a cron job because Midtrans will notify us via Webhook when it expires.
                "custom_expiry" => [
                    "expiry_duration" => 2, // 15 minutes
                    "unit" => "minute"
                ]
            ];

            // Call Midtrans with Mock Fallback
            try {
                $qris = CoreApi::charge($params);
                $order->payment_url = $qris->actions[0]->url ?? null;
                $order->qr_string = $qris->qr_string ?? null;
            } catch (\Exception $e) {
                Log::error('API MIDTRANS ERROR: ' . $e->getMessage());
                // Throw exception to let the user know something is wrong
                throw $e;
            }

            $order->save();

            return response()->json([
                'success' => true,
                'order_id' => $order->id,
                'order_code' => $order->code,
                'payment_url' => $order->payment_url,
                'qr_string' => $order->qr_string,
            ], 201);

        } catch (\Exception $e) {
            Log::error('API Create QRIS Failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create order: ' . $e->getMessage()
            ], 500);
        }
    }
    /**
     * DEBUG: Force payment success (manual trigger)
     */
    public function forcePaymentSuccess(Request $request, $tenantIdentifier, $tablenumber, $code)
    {
        if (!app()->environment(['local', 'development'])) {
            abort(404);
        }
        
        try {
            // Debug route - bypass tenant scope
            $order = Order::withoutGlobalScope('tenant')->where('code', $code)->firstOrFail();
            $order->status = 'paid';
            $order->payment_status = 'paid'; // Sync payment_status
            $order->completed_at = now();
            $order->save();
            
            Log::info('DEBUG: Manual force payment success', [
                'order_code' => $order->code,
                'status' => $order->status
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Payment status forced to paid',
                'order_code' => $order->code,
                'status' => $order->status
            ]);
            
        } catch (\Exception $e) {
            Log::error('DEBUG: Error forcing payment success', [
                'order_code' => $code,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to force payment success',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get tenant from identifier (slug-shortUuid format)
     * Security: Requires BOTH slug AND UUID to match
     */
    private function getTenantFromIdentifier($identifier)
    {
        // Check if identifier contains dash (slug-uuid format)
        if (strpos($identifier, '-') !== false) {
            // Split by last dash to get short_uuid
            $parts = explode('-', $identifier);
            $shortUuid = array_pop($parts); // Last part is UUID
            $slug = implode('-', $parts); // Rest is slug
            
            // Find tenant by BOTH slug AND short_uuid (double security)
            $tenant = Tenant::where('slug', $slug)
                ->where('short_uuid', $shortUuid)
                ->first();
            
            if ($tenant) {
                return $tenant;
            }
        }
        
        // Fallback: Try full UUID or short UUID only
        $tenant = Tenant::where('uuid', $identifier)
            ->orWhere('short_uuid', $identifier)
            ->first();
        
        if ($tenant) {
            return $tenant;
        }
        
        // Not found
        abort(404, 'Tenant not found. Please check your QR code or URL.');
    }

}
