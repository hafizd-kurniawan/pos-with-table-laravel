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
            $order->completed_at = now();
            
            // Kurangi stock ketika payment berhasil
            $this->decreaseProductStock($order);
            
            // Send notification to user
            $this->sendNotification('1 New Order', 'New order received from table ' . $order->table->name);
        } elseif (in_array($transaction, ['cancel', 'expire', 'deny'])) {
            $order->status = 'failed';
            
            // Tidak perlu restore stock karena stock belum dikurangi saat order dibuat
        } elseif ($transaction === 'pending') {
            $order->status = 'pending';
        }

        $order->save();

        return response()->json(['message' => 'OK']);
    }

    // Method for send notification to restaurant/user/driver
    public function sendNotification($title, $message)
    {
        //find user is login
        $user = User::where('is_login', true)->first();
        if ($user && $user->fcm_token) {
            $token = $user->fcm_token;

            // Kirim notifikasi ke perangkat Android
            $messaging = app('firebase.messaging');
            $notification = FirebaseNotification::create($title, $message);

            $message = CloudMessage::withTarget('token', $token)
                ->withNotification($notification);

            try {
                $messaging->send($message);
            } catch (\Exception $e) {
                Log::error('Failed to send notification', ['error' => $e->getMessage()]);
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
            'items.*.notes' => 'nullable|string|max:255',
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
        $orders = \App\Models\Order::with('orderItems.product')->get();
        return response()->json([
            'data' => $orders,
        ]);
    }

    // get order status complete
    public function completedOrders(Request $request)
    {
        $orders = \App\Models\Order::with(['orderItems.product', 'table'])
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
        $orders = \App\Models\Order::with(['orderItems.product', 'table'])
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
        $orders = \App\Models\Order::with(['orderItems.product', 'table'])
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
        $order = \App\Models\Order::with(['orderItems.product', 'table'])->findOrFail($id);
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
                
                // Validasi stock dan status produk
                if (!$product->isAvailable()) {
                    throw new \Exception("Product '{$product->name}' is not available");
                }
                
                if ($product->stock < $item['qty']) {
                    throw new \Exception("Insufficient stock for product '{$product->name}'. Available: {$product->stock}, Requested: {$item['qty']}");
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
                
                $subtotal += (int) $price * (int) $item['qty'];
                
                \Log::info('Product price validation', [
                    'product_id' => $item['product_id'],
                    'name' => $product->name,
                    'flutter_price' => $item['price'],
                    'db_price' => $product->price,
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
            ]);

            // Buat order items
            foreach ($cartItems as $item) {
                \App\Models\OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['qty'],
                    'price' => $item['price'],
                    'total' => $item['price'] * $item['qty'],
                    'note' => $item['note'] ?? null,
                ]);
            }

            // Generate QRIS via Midtrans
            $params = [
                "payment_type" => "qris",
                "transaction_details" => [
                    "order_id" => $order->code,
                    "gross_amount" => (int) $order->total_amount, // Pastikan integer
                ],
                "item_details" => array_merge(
                    collect($cartItems)->map(function ($item) {
                        return [
                            "id" => $item['product_id'],
                            "price" => (int) $item['price'], // Pastikan integer
                            "quantity" => (int) $item['qty'],
                            "name" => $item['name'],
                        ];
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
     * Mengembalikan stock produk (untuk cancel order)
     */
    private function restoreProductStock(Order $order)
    {
        try {
            foreach ($order->orderItems as $orderItem) {
                $product = $orderItem->product;
                
                if ($product) {
                    $product->increaseStock($orderItem->quantity);
                    
                    Log::info('Stock restored successfully', [
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'restored_qty' => $orderItem->quantity,
                        'current_stock' => $product->fresh()->stock,
                    ]);
                }
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
            Log::info('📱 Flutter POS - Save Order Request', [
                'data' => $request->all()
            ]);

            // Validate request
            $validatedData = $request->validate([
                'payment_amount' => 'required|numeric',
                'sub_total' => 'required|numeric',
                'tax' => 'required|numeric',
                'discount' => 'required|numeric',
                'service_charge' => 'required|numeric',
                'total' => 'required|numeric',
                'payment_method' => 'required|string',
                'total_item' => 'required|integer',
                'transaction_time' => 'required|string',
                'order_items' => 'required|array',
                'order_items.*.product_id' => 'required|integer',
                'order_items.*.quantity' => 'required|integer|min:1',
                'order_items.*.price' => 'required|numeric',
            ]);

            return \Illuminate\Support\Facades\DB::transaction(function () use ($request, $validatedData) {
                // Get table_number and handle 0 or null
                $tableNumber = $request->input('table_number', 0);
                $tableId = ($tableNumber && $tableNumber > 0) ? $tableNumber : 1; // Default to table 1 if 0 or null
                
                // Create order
                $order = Order::create([
                    'code' => 'POS-' . strtoupper(uniqid()),
                    'status' => $request->input('status', 'paid'),
                    'placed_at' => $request->input('transaction_time', now()),
                    'completed_at' => $request->input('status') === 'paid' ? now() : null,
                    'customer_name' => $request->input('customer_name') ?: 'Walk-in Customer',
                    'customer_phone' => $request->input('customer_phone', ''),
                    'customer_email' => $request->input('customer_email', ''),
                    'notes' => $request->input('notes', ''),
                    'table_id' => $tableId,
                    'order_type' => $request->input('order_type', 'dine_in'), // NEW: Save order type
                    'total_amount' => $validatedData['total'],
                    'payment_method' => $validatedData['payment_method'],
                    'payment_status' => $request->input('payment_status', 'paid'),
                    'tax_amount' => $validatedData['tax'], // FIX: Save to tax_amount
                    'tax_percentage' => $request->input('tax_percentage', 0), // NEW: Save percentage
                    'discount_amount' => $request->input('discount_amount', 0), // FIX: Save discount_amount
                    'service_charge_amount' => $validatedData['service_charge'], // FIX: Save to service_charge_amount
                    'service_charge_percentage' => $request->input('service_charge_percentage', 0), // NEW: Save percentage
                    'subtotal' => $validatedData['sub_total'],
                ]);

                Log::info('✅ Order created', [
                    'order_id' => $order->id,
                    'order_code' => $order->code,
                    'total' => $order->total_amount,
                ]);

                // Create order items
                foreach ($validatedData['order_items'] as $item) {
                    $order->orderItems()->create([
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'total' => $item['price'] * $item['quantity'],
                        'notes' => $item['notes'] ?? '',
                    ]);

                    // Decrease stock if payment is completed
                    if ($request->input('payment_status') === 'paid') {
                        $product = \App\Models\Product::find($item['product_id']);
                        if ($product) {
                            $product->decrement('stock', $item['quantity']);
                            Log::info('📦 Stock decreased', [
                                'product' => $product->name,
                                'quantity' => $item['quantity'],
                                'remaining' => $product->stock,
                            ]);
                        }
                    }
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Order saved successfully',
                    'data' => [
                        'order_id' => $order->id,
                        'order_code' => $order->code,
                        'total_amount' => $order->total_amount,
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
