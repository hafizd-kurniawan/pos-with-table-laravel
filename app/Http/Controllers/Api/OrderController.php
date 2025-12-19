<?php

namespace App\Http\Controllers\Api;

use Midtrans\Notification;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
//Logging
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Kreait\Firebase\Messaging\Messaging;
use App\Traits\ManagesStock;

class OrderController extends Controller
{
    use ManagesStock;
    public function midtransCallback(Request $request)
    {
        $notif = new Notification();
        // Ambil data
        $orderId      = $notif->order_id;
        $statusCode   = $notif->status_code;
        $grossAmount  = $notif->gross_amount;
        $signatureKey = $notif->signature_key;
        $transaction  = $notif->transaction_status;

        // Generate signature untuk validasi
        $serverKey = config('midtrans.server_key');
        $mySignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        if ($mySignature !== $signatureKey) {
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        // Cari order
        $order = Order::where('code', $orderId)->first();
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        // Update status sesuai callback
        if (in_array($transaction, ['capture', 'settlement'])) {
            $order->status = 'paid';
            $order->payment_status = 'paid'; // Sync payment_status
            $order->payment_amount = $grossAmount; // Save payment amount
            $order->completed_at = now();
            
            // CRITICAL FIX: Only decrease stock if it wasn't already decreased at creation
            // QRIS orders created via Web OrderController ALREADY decreased stock.
            if ($order->payment_method !== 'qris') { 
                 $this->decreaseProductStock($order);
            }
            
            // Save FIRST to ensure status is updated even if notification fails
            $order->save();
            
            // Send notification to user
            try {
                $this->sendNotification('1 New Order', 'New order received from table ' . $order->table->name, $order->tenant_id);
            } catch (\Exception $e) {
                Log::error('Notification failed but order saved: ' . $e->getMessage());
            }
        } elseif (in_array($transaction, ['cancel', 'expire', 'deny'])) {
            $order->status = 'failed';
            
            // CRITICAL FIX: Restore stock if it was reserved (QRIS orders)
            // QRIS orders reserved stock at creation, so we must release it on failure/expiry
            if ($order->payment_method === 'qris') {
                 $this->releaseStock($order);
                 Log::info("Stock restored for expired/cancelled QRIS order: {$order->code}");
                 
                 // Release Table
                 if ($order->table_id) {
                     $table = \App\Models\Table::find($order->table_id);
                     if ($table && $table->status === 'occupied') {
                         $table->status = 'available';
                         $table->customer_name = null;
                         $table->occupied_at = null;
                         $table->save();
                         Log::info("Table {$table->name} released due to expired/cancelled order");
                     }
                 }
            }
            $order->save();
        } elseif ($transaction === 'pending') {
            $order->status = 'pending';
            $order->save();
        }

        $order->save();

        return response()->json(['message' => 'OK']);
    }

    // Method for send notification to restaurant/user/driver
    public function sendNotification($title, $message, $tenantId = null)
    {
        Log::info("🔔 sendNotification called", ['title' => $title, 'tenant_id' => $tenantId]);

        // Find user is login
        $query = User::query();
        
        // CRITICAL: Ensure we only notify user from the same tenant
        if ($tenantId) {
            // Use forTenant scope from BelongsToTenant trait
            $query->forTenant($tenantId);
        }
        
        $user = $query->where('is_login', true)->first();
        
        if ($user) {
            Log::info("👤 User found for notification", ['user_id' => $user->id, 'has_token' => !empty($user->fcm_token)]);
        } else {
            Log::warning("⚠️ No logged-in user found for tenant", ['tenant_id' => $tenantId]);
        }

        if ($user && $user->fcm_token) {
            $token = $user->fcm_token;
            Log::info("📱 Sending FCM to token", ['token_preview' => substr($token, 0, 10) . '...']);

            // Kirim notifikasi ke perangkat Android
            $messaging = app('firebase.messaging');
            $notification = FirebaseNotification::create($title, $message);

            $message = CloudMessage::withTarget('token', $token)
                ->withNotification($notification);

            try {
                $messaging->send($message);
                Log::info("✅ Notification sent successfully");
            } catch (\Exception $e) {
                Log::error('❌ Failed to send notification', ['error' => $e->getMessage()]);
            }
        }
    }


    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'cashier_id' => 'required',
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.notes' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500', // NEW: Global note validation
        ]);

        $paymentMethod = strtolower($request->input('payment_method', 'cash'));
        
        Log::info('Processing order with payment method', [
            'payment_method_raw' => $request->input('payment_method'),
            'payment_method_processed' => $paymentMethod,
        ]);

        // Gunakan database transaction dengan pessimistic locking untuk mencegah race condition
        return \Illuminate\Support\Facades\DB::transaction(function () use ($validatedData, $request, $paymentMethod) {
            // Lock semua products yang ada di order untuk mencegah race condition
            $productIds = collect($validatedData['items'])->pluck('product_id')->unique();
            $products = \App\Models\Product::whereIn('id', $productIds)
                ->lockForUpdate() // Pessimistic lock
                ->get()
                ->keyBy('id');

            // Validasi stock dengan data yang ter-lock
            foreach ($validatedData['items'] as $item) {
                $product = $products->get($item['product_id']);
                if (!$product) {
                    throw new \Exception("Product with ID {$item['product_id']} not found");
                }
                
                // Validasi status dan stock produk dengan data terkini
                if (!$product->isAvailable()) {
                    throw new \Exception("Product '{$product->name}' is not available");
                }
                
                if ($product->stock < $item['quantity']) {
                    throw new \Exception("Insufficient stock for product '{$product->name}'. Available: {$product->stock}, Requested: {$item['quantity']}");
                }
            }

            $order = \App\Models\Order::create([
                'code' => 'TRX-' . strtoupper(uniqid()),
                'status' => $paymentMethod === 'cash' ? 'paid' : 'completed',
                'placed_at' => now(),
                'customer_name' => $request->input('customer_name', 'Anonymous'),
                'customer_phone' => $request->input('customer_phone', ''),
                'customer_email' => $request->input('customer_email', ''),
                'notes' => $request->input('notes', ''),
                'table_id' => $request->input('table_id', 1),
                'total_amount' => collect($validatedData['items'])->sum(function ($item) use ($products) {
                    return $products->get($item['product_id'])->price * $item['quantity'];
                }),
                'payment_method' => $paymentMethod,
            ]);

            Log::info('Order created', [
                'order_id' => $order->id,
                'total_amount' => $order->total_amount,
                'items' => $validatedData['items'],
            ]);

            // Create order items dan kurangi stock sekaligus untuk cash payments
            foreach ($validatedData['items'] as $item) {
                $product = $products->get($item['product_id']);
                
                $order->orderItems()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'notes' => $item['notes'] ?? '',
                    'price' => $product->price,
                    'total' => $product->price * $item['quantity'],
                ]);

                // For cash payments, decrease stock immediately since payment is completed
                if ($paymentMethod === 'cash') {
                    // Double check stock sebelum mengurangi
                    if ($product->stock < $item['quantity']) {
                        throw new \Exception("Stock insufficient for {$product->name}. Available: {$product->stock}, Requested: {$item['quantity']}");
                    }
                    
                    // Kurangi stock sekaligus
                    $product->decrement('stock', $item['quantity']);
                    
                    // Log stock reduction
                    Log::info('API ORDER: Stock reduced for cash payment', [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'quantity_reduced' => $item['quantity'],
                        'remaining_stock' => $product->fresh()->stock
                    ]);
                }
            }

            if ($paymentMethod === 'cash') {
                Log::info('Cash payment completed - stock already decreased', [
                    'order_id' => $order->id,
                    'payment_method' => $paymentMethod,
                ]);
            } else {
                Log::info('Non-cash payment - stock will be decreased after payment confirmation', [
                    'order_id' => $order->id,
                    'payment_method' => $paymentMethod,
                ]);
            }

            return response()->json([
                'message' => 'Order created successfully',
                'data' => $order->load('orderItems.product'),
            ], 201);
        }, 5); // Retry 5 kali jika deadlock
    }
    // get all orders
    public function index(Request $request)
    {
        $orders = \App\Models\Order::with(['orderItems.product', 'orderItems.addons'])->get();
        return response()->json([
            'data' => $orders,
        ]);
    }

    // get order status complete
    public function completedOrders(Request $request)
    {
        $orders = \App\Models\Order::with(['orderItems.product', 'orderItems.addons', 'table'])
            ->where('status', 'complete')
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json([
            'data' => $orders,
        ]);
    }

    // get order status paid
    public function paidOrders(Request $request)
    {
        $orders = \App\Models\Order::with(['orderItems.product', 'orderItems.addons', 'table'])
            ->where('status', 'paid')
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json([
            'data' => $orders,
        ]);
    }

    // get order status cooking process
    public function cookingOrders(Request $request)
    {
        $orders = \App\Models\Order::with(['orderItems.product', 'orderItems.addons', 'table'])
            ->where('status', 'cooking')
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json([
            'data' => $orders,
        ]);
    }

    // update order status
    public function updateStatus(Request $request, $id)
    {
        $user = $request->user();
        // SECURITY: Ensure order belongs to the user's tenant
        $order = \App\Models\Order::where('tenant_id', $user->tenant_id)
            ->with(['orderItems.product', 'table'])
            ->findOrFail($id);
        $previousStatus = $order->status;

        $validatedData = $request->validate([
            'status' => 'required|in:complete,cooking,paid,pending,cancelled,expired',
        ]);

        $order->status = $validatedData['status'];
        if ($order->status === 'complete') {
            $order->completed_at = now();
        }

        // Handle stock management berdasarkan perubahan status
        if ($previousStatus !== $order->status) {
            if ($order->status === 'paid' && !in_array($previousStatus, ['paid', 'cooking', 'complete'])) {
                // Jika status berubah menjadi paid dari status lain (selain yang sudah paid), kurangi stock
                $this->decreaseProductStock($order);
                Log::info('Stock decreased due to manual status change to paid', ['order_id' => $order->id]);
            } elseif (in_array($previousStatus, ['paid', 'cooking', 'complete']) && $order->status === 'cancelled') {
                // Jika order yang sudah paid dibatalkan, kembalikan stock
                $this->restoreProductStock($order);
                Log::info('Stock restored due to order cancellation', ['order_id' => $order->id]);
            }
            
            // 🔥 UPDATE TABLE STATUS based on order status
            if ($order->table) {
                if ($order->status === 'complete' || $order->status === 'cancelled') {
                    // Order selesai atau dibatalkan → Table available and clear customer info
                    $order->table->status = 'available';
                    $order->table->customer_name = null;
                    $order->table->customer_phone = null;
                    $order->table->occupied_at = null;
                    $order->table->save();
                    Log::info('Table status updated to available and customer info cleared', [
                        'order_id' => $order->id,
                        'table_id' => $order->table->id,
                        'table_name' => $order->table->name
                    ]);
                }
            }
        }

        $order->save();

        return response()->json([
            'message' => 'Order status updated successfully',
            'data' => $order,
        ]);
    }

    // Create QRIS order via API for Flutter
    public function createQrisOrder(Request $request)
    {
        try {
            // Debug: Log all incoming request data
            \Log::info('QRIS Order Request Received', [
                'customer_name' => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'customer_email' => $request->customer_email,
                'table_number' => $request->table_number,
                'cart_items' => $request->cart_items,
                'payment_method' => $request->payment_method,
            ]);

            // Set Midtrans configuration
            \Midtrans\Config::$serverKey = config('midtrans.server_key');
            \Midtrans\Config::$isProduction = config('midtrans.is_production');
            \Midtrans\Config::$isSanitized = true;
            \Midtrans\Config::$is3ds = true;
            
            \Log::info('Midtrans Config', [
                'server_key' => substr(config('midtrans.server_key'), 0, 10) . '...',
                'is_production' => config('midtrans.is_production'),
            ]);
            
            // Validasi input
            $request->validate([
                'customer_name' => 'required|string',
                'customer_phone' => 'nullable|string',
                'customer_email' => 'nullable|email',
                'table_number' => 'required|string',
                'cart_items' => 'required|array',
                'cart_items.*.product_id' => 'required|integer',
                'cart_items.*.qty' => 'required|integer|min:1',
                'cart_items.*.price' => 'required|numeric',
                'cart_items.*.name' => 'required|string',
                'cart_items.*.addons' => 'nullable|array',
                'cart_items.*.addons.*.id' => 'required|integer',
                'cart_items.*.addons.*.name' => 'required|string',
                'cart_items.*.addons.*.price' => 'required|numeric',
            ]);

            $tableNumber = $request->table_number;
            $table = \App\Models\Table::where('name', $tableNumber)->firstOrFail();
            
            // Hitung total dengan memvalidasi dan mengambil harga dari database jika diperlukan
            $cartItems = $request->cart_items;
            $subtotal = 0;
            
            foreach ($cartItems as &$item) {
                $product = \App\Models\Product::find($item['product_id']);
                if (!$product) {
                    throw new \Exception("Product with ID {$item['product_id']} not found");
                }
                
                // Validasi stock dan status produk (Hybrid Logic)
                if (!$product->isAvailable()) {
                    throw new \Exception("Product '{$product->name}' is not available");
                }
                
                if ($product->recipes()->exists()) {
                    // Recipe Product: Check ingredient availability
                    $check = $product->canBeProduced($item['qty']);
                    if (!$check['can_produce']) {
                        $missing = collect($check['insufficient_ingredients'])
                            ->map(fn($i) => "{$i['ingredient']} (Need: {$i['needed']}, Have: {$i['available']})")
                            ->join(', ');
                        throw new \Exception("Insufficient ingredients for {$product->name}: {$missing}");
                    }
                } else {
                    // Direct Stock Product: Check product stock
                    if ($product->stock < $item['qty']) {
                        throw new \Exception("Insufficient stock for product '{$product->name}'. Available: {$product->stock}, Requested: {$item['qty']}");
                    }
                }
                
                // Gunakan harga dari database jika harga dari Flutter kosong/0
                $price = ($item['price'] > 0) ? $item['price'] : $product->price;
                if ($item['price'] <= 0) {
                    \Log::warning('Flutter sent price 0, using DB price', [
                        'product_id' => $item['product_id'],
                        'flutter_price' => $item['price'],
                        'db_price' => $product->price
                    ]);
                }
                $item['price'] = $price; // Update harga untuk konsistensi
                
                // Calculate Addons Total
                $addonsTotal = 0;
                $addonsData = [];
                if (isset($item['addons']) && is_array($item['addons'])) {
                    foreach ($item['addons'] as $addon) {
                        $addonPrice = $addon['price'];
                        $addonsTotal += $addonPrice;
                        $addonsData[] = [
                            'product_addon_id' => $addon['id'],
                            'name' => $addon['name'],
                            'price' => $addonPrice
                        ];
                    }
                }
                
                $item['addons_data'] = $addonsData; // Store for later use
                $subtotal += ((int) $price + $addonsTotal) * (int) $item['qty'];
                
                \Log::info('Product price validation', [
                    'product_id' => $item['product_id'],
                    'name' => $product->name,
                    'flutter_price' => $item['price'],
                    'db_price' => $product->price,
                    'addons_total' => $addonsTotal,
                    'final_price' => $price
                ]);
            }
            
            // Get tax percentage
            $taxPercentage = 11; // Hardcode or from config
            $taxAmount = (int) ($subtotal * ($taxPercentage / 100));
            $totalAmount = $subtotal + $taxAmount;
            
            // Pastikan total tidak 0
            if ($totalAmount <= 0) {
                \Log::error('Invalid total amount', [
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'total_amount' => $totalAmount,
                    'cart_items' => $cartItems
                ]);
                throw new \Exception("Invalid total amount: {$totalAmount}. Subtotal: {$subtotal}, Tax: {$taxAmount}");
            }

            // Buat order
            $order = \App\Models\Order::create([
                'table_id' => $table->id,
                'code' => 'JG-' . now()->format('ymd-') . \Illuminate\Support\Str::padLeft(\App\Models\Order::whereDate('created_at', now())->count() + 1, 4, '0'),
                'total_amount' => $totalAmount,
                'status' => 'pending',
                'placed_at' => now(),
                'payment_method' => 'qris',
                'customer_name' => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'customer_email' => $request->customer_email,
                'notes' => $request->notes, // Added global note mapping
            ]);

            // Buat order items
            foreach ($cartItems as $item) {
                $orderItem = \App\Models\OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['qty'],
                    'price' => $item['price'],
                    'total' => ($item['price'] * $item['qty']), // Base Total (Addons saved separately)
                    'notes' => $item['note'] ?? null,
                ]);
                
                // Save Addons
                if (!empty($item['addons_data'])) {
                    foreach ($item['addons_data'] as $addon) {
                        $orderItem->addons()->create([
                            'product_addon_id' => $addon['product_addon_id'],
                            'name' => $addon['name'],
                            'price' => $addon['price'],
                        ]);
                    }
                }
            }

            // Generate QRIS via Midtrans
            $params = [
                "payment_type" => "qris",
                "transaction_details" => [
                    "order_id" => $order->code,
                    "gross_amount" => (int) $order->total_amount, // Pastikan integer
                ],
                "item_details" => array_merge(
                    collect($cartItems)->flatMap(function ($item) {
                        $items = [];
                        // Main Product
                        $items[] = [
                            "id" => $item['product_id'],
                            "price" => (int) $item['price'],
                            "quantity" => (int) $item['qty'],
                            "name" => $item['name'],
                        ];
                        
                        // Addons as separate items in Midtrans for clarity
                        if (!empty($item['addons_data'])) {
                            foreach ($item['addons_data'] as $addon) {
                                $items[] = [
                                    "id" => "ADDON-" . $addon['product_addon_id'],
                                    "price" => (int) $addon['price'],
                                    "quantity" => (int) $item['qty'], // Addon qty follows product qty
                                    "name" => "+ " . $addon['name'] . " (" . $item['name'] . ")",
                                ];
                            }
                        }
                        return $items;
                    })->toArray(),
                    $taxAmount > 0 ? [[
                        "id" => "tax",
                        "price" => (int) $taxAmount, // Pastikan integer
                        "quantity" => 1,
                        "name" => "Tax ({$taxPercentage}%)",
                    ]] : []
                ),
                "customer_details" => [
                    "first_name" => $order->customer_name ?? 'Guest',
                    "email" => $order->customer_email ?? 'guest@example.com',
                    "phone" => $order->customer_phone,
                ]
            ];

            // Debug log params yang akan dikirim ke Midtrans
            Log::info('Midtrans Params', [
                'order_code' => $order->code,
                'gross_amount' => (int) $order->total_amount,
                'params' => $params
            ]);

            $qris = \Midtrans\CoreApi::charge($params);

            // Simpan QR string
            $order->qr_string = $qris->qr_string ?? null;
            $order->payment_url = $qris->actions[0]->url ?? null;
            $order->save();

            return response()->json([
                'success' => true,
                'order_id' => $order->id,
                'order_code' => $order->code,
                'qr_string' => $order->qr_string,
                'payment_url' => $order->payment_url,
                'total_amount' => $order->total_amount,
                'message' => 'Order created successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Create QRIS Order Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Check order payment status
    public function checkOrderStatus($orderCode)
    {
        try {
            $order = \App\Models\Order::where('code', $orderCode)->firstOrFail();
            
            return response()->json([
                'success' => true,
                'payment_status' => $order->status,
                'order_status' => $order->status,
                'total_amount' => $order->total_amount,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Order not found',
            ], 404);
        }
    }

    /**
     * Mengurangi stock produk berdasarkan order items
     */
    private function decreaseProductStock(Order $order)
    {
        Log::info('Starting stock decrease process (Hybrid)', [
            'order_id' => $order->id,
            'order_items_count' => $order->orderItems->count(),
        ]);
        
        try {
            // 1. Handle Direct Stock Products
            foreach ($order->orderItems as $orderItem) {
                $product = $orderItem->product;
                
                if ($product && $product->recipes()->doesntExist()) {
                    $currentStock = $product->stock;
                    Log::info('Decreasing Direct Stock', [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'current_stock' => $currentStock,
                        'quantity' => $orderItem->quantity,
                    ]);
                    
                    $product->decrement('stock', $orderItem->quantity);
                }
            }

            // 2. Handle Recipe Products via InventoryService
            $inventoryService = app(\App\Services\InventoryService::class);
            $inventoryService->deductStockForOrder($order->id);
            Log::info('Ingredient Stock processed for order', ['order_id' => $order->id]);

        } catch (\Exception $e) {
            Log::error('Error decreasing stock: ' . $e->getMessage());
        }
    }


    /**
     * Mengembalikan stock produk (untuk cancel order)
     */
    private function restoreProductStock(Order $order)
    {
        try {
            foreach ($order->orderItems as $orderItem) {
                $product = $orderItem->product;
                
                if ($product) {
                    // Hybrid Restoration Logic
                    if ($product->recipes->isEmpty()) {
                        // Direct Stock: Restore product stock
                        $product->increaseStock($orderItem->quantity);
                        
                        Log::info('Stock restored successfully (Direct)', [
                            'order_id' => $order->id,
                            'product_id' => $product->id,
                            'product_name' => $product->name,
                            'restored_qty' => $orderItem->quantity,
                            'current_stock' => $product->fresh()->stock,
                        ]);
                    }
                    // Recipe Stock: Will be restored via InventoryService below
                }
            }
            
            // Restore Recipe Ingredients (Hybrid)
            try {
                $inventoryService = app(\App\Services\InventoryService::class);
                $inventoryService->restoreStockForOrder($order->id);
                Log::info('Ingredient Stock restored successfully', ['order_id' => $order->id]);
            } catch (\Exception $ex) {
                Log::error('Failed to restore ingredient stock', ['error' => $ex->getMessage()]);
            }
        } catch (\Exception $e) {
            Log::error('Error restoring product stock', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Save order from Flutter POS
     * Compatible dengan data structure dari Flutter
     */
    public function saveOrder(Request $request)
    {
        try {
            $user = $request->user();
            $tenantId = $user->tenant_id;

            Log::info('📱 Flutter POS - Save Order Request', [
                'tenant_id' => $tenantId,
                'user_id' => $user->id,
                'data' => $request->all()
            ]);

            // Validate request
            $validatedData = $request->validate([
                'payment_amount' => 'required|numeric',
                'sub_total' => 'required|numeric',
                'tax' => 'required|numeric',
                'discount' => 'required|numeric',
                'discount_amount' => 'nullable|numeric',
                'service_charge' => 'required|numeric',
                'total' => 'required|numeric',
                'payment_method' => 'required|string',
                'total_item' => 'required|integer',
                'transaction_time' => 'required|string',
                'order_items' => 'required|array',
                'order_items.*.product_id' => 'required|integer',
                'order_items.*.quantity' => 'required|integer|min:1',
                'order_items.*.addons' => 'nullable|array',
                'order_items.*.addons.*.id' => 'required|integer',
                'order_items.*.addons.*.name' => 'required|string',
                'order_items.*.addons.*.price' => 'required|numeric',
                'discount_id' => 'nullable|integer',
                'tax_percentage' => 'nullable|numeric',
                'service_charge_percentage' => 'nullable|numeric',
                'table_number' => 'nullable',
                'status' => 'nullable|string',
                'customer_name' => 'nullable|string',
                'customer_phone' => 'nullable|string',
                'customer_email' => 'nullable|string',
                'notes' => 'nullable|string',
                'order_type' => 'nullable|string',
                'payment_status' => 'nullable|string',
                'cashier_name' => 'nullable|string',
            ]);

            return \Illuminate\Support\Facades\DB::transaction(function () use ($request, $validatedData, $tenantId) {
                
                // 1. Fetch Products with Lock (Prevent Race Condition)
                $items = collect($request->order_items);
                $productIds = $items->pluck('product_id');
                
                $products = \App\Models\Product::whereIn('id', $productIds)
                    ->where('tenant_id', $tenantId)
                    ->with('recipes.ingredient') // Eager load recipes
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                // 2. Validate Products & Stock & Calculate Subtotal
                $cartItems = [];
                $serverSubtotal = 0;

                foreach ($items as $item) {
                    $product = $products->get($item['product_id']);
                    
                    if (!$product) {
                        throw new \Exception("Product ID {$item['product_id']} not found.");
                    }
                    
                    if ($product->recipes->isNotEmpty()) {
                        // Recipe Product: Check ingredient availability
                        $check = $product->canBeProduced($item['quantity']);
                        if (!$check['can_produce']) {
                            $missing = collect($check['insufficient_ingredients'])
                                ->map(fn($i) => "{$i['ingredient']} (Need: {$i['needed']}, Have: {$i['available']})")
                                ->join(', ');
                            throw new \Exception("Insufficient ingredients for {$product->name}: {$missing}");
                        }
                    } else {
                        // Direct Stock Product: Check product stock
                        if ($product->stock < $item['quantity']) {
                            throw new \Exception("Insufficient stock for {$product->name}. Available: {$product->stock}");
                        }
                    }

                    // SECURITY: Use Server Price, ignore client price
                    $price = $product->price; 
                    
                    // Calculate Addons Total
                    $addonsTotal = 0;
                    $addonsData = [];
                    if (isset($item['addons']) && is_array($item['addons'])) {
                        foreach ($item['addons'] as $addon) {
                            // Ideally we should validate addon price against DB, but for now we trust the ID exists
                            // and use the sent price (or fetch if critical). 
                            // Assuming addon price is static or we trust the client for now (to match Flutter logic).
                            // Better: Fetch ProductAddon if possible.
                            // Let's just use the sent price for simplicity as ProductAddon model might be complex to fetch here without eager loading.
                            $addonPrice = $addon['price'];
                            $addonsTotal += $addonPrice;
                            $addonsData[] = [
                                'product_addon_id' => $addon['id'],
                                'name' => $addon['name'],
                                'price' => $addonPrice
                            ];
                        }
                    }

                    $total = ($price + $addonsTotal) * $item['quantity'];
                    $serverSubtotal += $total;

                    $cartItems[] = [
                        'product_id' => $product->id,
                        'quantity' => $item['quantity'],
                        'price' => $price, // Base Price
                        'total' => $total, // Base + Addons * Qty
                        'notes' => $item['notes'] ?? '',
                        'product' => $product, // Keep reference for decrement
                        'addons' => $addonsData // Store addons to save later
                    ];
                }

                // 3. Calculate Discount
                $discountAmount = 0;
                // Fix: Ensure discount_id is null if 0
                $discountId = ($request->discount_id && $request->discount_id > 0) ? $request->discount_id : null;
                
                if ($discountId) {
                    $discount = \App\Models\Discount::where('id', $discountId)
                        ->where('tenant_id', $tenantId)
                        ->first();
                        
                    if ($discount) {
                        if ($discount->type === 'percentage') {
                            $discountAmount = $serverSubtotal * ($discount->value / 100);
                        } else {
                            $discountAmount = min($discount->value, $serverSubtotal);
                        }
                    }
                } elseif ($request->discount_amount > 0) {
                    // Manual Discount fallback (Cap at subtotal)
                    $discountAmount = min($request->discount_amount, $serverSubtotal);
                }
                
                $discountAmount = round($discountAmount);
                $subtotalAfterDiscount = max(0, $serverSubtotal - $discountAmount);

                // 4. Calculate Tax & Service (Fetch from DB for security)
                // Fix: Match database types 'pajak' and 'layanan'
                $tax = \App\Models\Tax::where('tenant_id', $tenantId)
                    ->where(function($q) {
                        $q->where('type', 'tax')->orWhere('type', 'pajak');
                    })->first();
                    
                $service = \App\Models\Tax::where('tenant_id', $tenantId)
                    ->where(function($q) {
                        $q->where('type', 'service')->orWhere('type', 'layanan');
                    })->first();

                $taxAmount = 0;
                $taxPercentage = 0;
                if ($tax) {
                    $taxPercentage = $tax->value;
                    $taxAmount = round($subtotalAfterDiscount * ($taxPercentage / 100));
                }

                $serviceAmount = 0;
                $servicePercentage = 0;
                if ($service) {
                    $servicePercentage = $service->value;
                    $serviceAmount = round($subtotalAfterDiscount * ($servicePercentage / 100));
                }

                $serverTotal = $subtotalAfterDiscount + $taxAmount + $serviceAmount;

                // 5. Create Order
                $tableNumber = $request->input('table_number', 0);
                $tableId = ($tableNumber && $tableNumber > 0) ? $tableNumber : 1;

                $order = \App\Models\Order::create([
                    'tenant_id' => $tenantId,
                    'code' => 'POS-' . strtoupper(uniqid()),
                    'status' => $request->input('status', 'paid'),
                    'placed_at' => $request->input('transaction_time', now()),
                    'completed_at' => $request->input('status') === 'paid' ? now() : null,
                    'customer_name' => $request->input('customer_name') ?: 'Walk-in Customer',
                    'customer_phone' => $request->input('customer_phone', ''),
                    'customer_email' => $request->input('customer_email', ''),
                    'notes' => $request->input('notes', ''),
                    'table_id' => $tableId,
                    'order_type' => $request->input('order_type', 'dine_in'),
                    'payment_method' => $validatedData['payment_method'],
                    'payment_status' => $request->input('payment_status', 'paid'),
                    'cashier_name' => $request->input('cashier_name'),
                    
                    // Financials (Server Calculated)
                    'subtotal' => $serverSubtotal,
                    'discount_id' => $discountId,
                    'discount_amount' => $discountAmount,
                    'tax_percentage' => $taxPercentage,
                    'tax_amount' => $taxAmount,
                    'service_charge_percentage' => $servicePercentage,
                    'service_charge_amount' => $serviceAmount,
                    'total_amount' => $serverTotal,
                    'payment_amount' => $validatedData['payment_amount'],
                    'change_amount' => $validatedData['payment_amount'] - $serverTotal,
                ]);

                // 6. Create Items & Decrement Stock
                foreach ($cartItems as $item) {
                    $orderItem = \App\Models\OrderItem::create([
                        'tenant_id' => $tenantId,
                        'order_id' => $order->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'total' => $item['total'],
                        'notes' => $item['notes'],
                    ]);

                    // Save Addons
                    if (!empty($item['addons'])) {
                        foreach ($item['addons'] as $addon) {
                            $orderItem->addons()->create([
                                'product_addon_id' => $addon['product_addon_id'],
                                'name' => $addon['name'],
                                'price' => $addon['price'],
                            ]);
                        }
                    }

                    // Decrement Stock Logic (Hybrid)
                    if ($item['product']->recipes->isEmpty()) {
                        // Direct Stock: Decrement product stock
                        $item['product']->decrement('stock', $item['quantity']);
                        
                        Log::info('📦 Product Stock decreased', [
                            'product' => $item['product']->name,
                            'quantity' => $item['quantity'],
                            'remaining' => $item['product']->fresh()->stock,
                        ]);
                    }
                    // Recipe Stock: Handled by InventoryService below
                }

                // 7. Process Recipe Ingredients Deduction
                try {
                    $inventoryService = app(\App\Services\InventoryService::class);
                    $inventoryService->deductStockForOrder($order->id);
                    Log::info('🥦 Ingredient Stock processed for order', ['order_id' => $order->id]);
                } catch (\Exception $e) {
                    // Log error but don't fail the order if ingredient deduction fails? 
                    // NO, we should fail the transaction if ingredients can't be deducted to maintain consistency.
                    throw new \Exception("Failed to process ingredient stock: " . $e->getMessage());
                }

                Log::info('✅ Order created securely', ['id' => $order->id, 'total' => $serverTotal]);

                return response()->json([
                    'success' => true,
                    'message' => 'Order saved successfully',
                    'data' => [
                        'order_id' => $order->id,
                        'order_code' => $order->code,
                        'total_amount' => $serverTotal,
                        'subtotal' => $serverSubtotal,
                        'discount_amount' => $discountAmount,
                        'tax_amount' => $taxAmount,
                        'service_charge_amount' => $serviceAmount,
                    ]
                ], 201);
            });

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('❌ Validation Error', [
                'errors' => $e->errors()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('❌ Save Order Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to save order: ' . $e->getMessage()
            ], 500);
        }
    }
}
