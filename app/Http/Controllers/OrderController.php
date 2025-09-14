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
use App\Traits\ManagesStock;

class OrderController extends Controller
{
    use ManagesStock;
    // Tampil menu berdasarkan nomor meja
    public function index($tablenumber)
    {
        $allProducts = \App\Models\Product::where('status', 'available')->orderBy('name')->get();
        $allCategory = (object)[
            'id' => 0,
            'name' => 'All',
            'products' => $allProducts
        ];
        $table = Table::where('name', $tablenumber)->firstOrFail();
        $categories = Category::with(['products' => function ($q) {
            $q->where('status', 'available')
                ->orderBy('name');
        }])->get();
        $categories = collect([$allCategory])->concat($categories);
        return view('order.menu', compact('table', 'categories'));
    }


    // Tambah ke keranjang (session) dari menu page
    public function addToCart(Request $request, $tablenumber)
    {
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

            $cartKey = 'cart_' . $tablenumber;
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
    public function addToCartAjax(Request $request, $tablenumber)
    {
        try {
            $product = Product::findOrFail($request->input('product_id'));
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

            $cartKey = 'cart_' . $tablenumber;
            $cart = session($cartKey, []);

            // Debug: Log current cart state BEFORE any changes
            Log::info('AJAX ADD CART - BEFORE', [
                'table' => $tablenumber,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'cart_key' => $cartKey,
                'current_cart' => $cart,
                'current_cart_count' => count($cart),
                'session_id' => session()->getId()
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

            // Debug session cart
            Log::info('AJAX Cart Debug', [
                'table' => $tablenumber,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'qty_added' => $qtyChange,
                'cart_after_update' => $cart,
                'session_key' => $cartKey,
                'session_cart' => session($cartKey)
            ]);

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
    public function cart($tablenumber)
    {
        $cart = session('cart_' . $tablenumber, []);
        $table = Table::where('name', $tablenumber)->firstOrFail();
        
        // Validasi stock untuk semua items di cart
        $stockValidation = $this->validateCartStock($cart);
        
        return view('order.cart', compact('cart', 'table', 'stockValidation'));
    }

    // Form checkout (isi nama/phone opsional)
    public function checkoutForm($tablenumber)
    {
        $cart = session('cart_' . $tablenumber, []);
        $table = Table::where('name', $tablenumber)->firstOrFail();
        
        // Pre-validate cart sebelum tampilkan form checkout
        $stockValidation = $this->validateCartStock($cart);
        
        if (!$stockValidation['is_valid']) {
            return redirect()->route('order.cart', $tablenumber)
                ->with('error', 'Some items in your cart have stock issues: ' . implode(', ', $stockValidation['errors']));
        }
        
        return view('order.checkout', compact('cart', 'table'));
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

    public function removeCart($tablenumber, $productId)
    {
        $cart = session('cart_' . $tablenumber, []);
        foreach ($cart as $i => $item) {
            if ($item['product_id'] == $productId) {
                unset($cart[$i]);
                break;
            }
        }

        $cart = array_values($cart);
        session(['cart_' . $tablenumber => $cart]);

        return redirect()->route('order.cart', $tablenumber);
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

    public function checkout(Request $request, $tablenumber)
    {
        Log::info('CHECKOUT: masuk ke method checkout', [
            'table' => $tablenumber,
            'request' => $request->all()
        ]);

        // Pre-validate input
        $cart = session('cart_' . $tablenumber, []);
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
            return DB::transaction(function () use ($request, $tablenumber, $cart) {
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

                $table = Table::where('name', $tablenumber)->firstOrFail();
                $paymentMethod = $request->input('payment_method', 'qris');

                [$total_amount_taxt, $tax_amount] = $this->calculateTax($cart);
                
                // Create order
                $order = Order::create([
                    'table_id' => $table->id,
                    'code' => 'JG-' . now()->format('ymd-') . Str::padLeft(Order::whereDate('created_at', now())->count() + 1, 4, '0'),
                    'total_amount' => $total_amount_taxt,
                    'status' => 'pending',
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
                        'order_id' => $order->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['qty'],
                        'price' => $item['price'],
                        'total' => $item['price'] * $item['qty'],
                        'note' => $item['note'] ?? null,
                    ]);
                }

                // Clear cart after successful order creation
                session()->forget('cart_' . $tablenumber);

                // Handle payment methods
                return $this->processPayment($order, $cart, $paymentMethod, $tablenumber, $tax_amount);

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
    private function processPayment($order, $cart, $paymentMethod, $tablenumber, $tax_amount)
    {
        $params = [
            "transaction_details" => [
                "order_id"      => $order->code,
                "gross_amount"  => $order->total_amount,
            ],
            "item_details" => array_merge(
                collect($cart)->map(function ($i) {
                    return [
                        "id"       => $i['product_id'],
                        "price"    => $i['price'],
                        "quantity" => $i['qty'],
                        "name"     => $i['name'] ?? 'Menu',
                    ];
                })->toArray(),
                $tax_amount > 0 ? [[
                    "id"       => "tax",
                    "price"    => $tax_amount,
                    "quantity" => 1,
                    "name"     => "Tax ({$order->tax_percentage}%)",
                ]] : []
            ),
            "customer_details" => [
                "first_name" => $order->customer_name ?? 'Guest',
                "email"      => $order->customer_email ?? 'guest@example.com',
                "phone"      => $order->customer_phone,
            ]
        ];

        switch ($paymentMethod) {
            case 'qris':
                return $this->processQrisPayment($order, $params, $tablenumber);
            
            case 'gopay':
                return $this->processGopayPayment($order, $params, $tablenumber);
            
            case 'cash':
                // For cash, order is immediately completed
                $order->status = 'completed';
                $order->completed_at = now();
                $order->save();
                return redirect()->route('order.success', [$tablenumber, $order->code]);
            
            default:
                return redirect()->route('order.success', [$tablenumber, $order->code]);
        }
    }

    private function processQrisPayment($order, $params, $tablenumber)
    {
        $params["payment_type"] = "qris";
        Log::info('CHECKOUT: memanggil CoreApi::charge', $params);

        $qris = CoreApi::charge($params);

        $order->payment_url = $qris->actions[0]->url ?? null;
        $order->qr_string = $qris->qr_string ?? null;
        $order->save();

        Log::info('CHECKOUT: QRIS berhasil dibuat', [
            'order_id' => $order->id,
            'qr_string' => $order->qr_string,
            'payment_url' => $order->payment_url
        ]);

        // Send webhook notification
        $this->sendWebhookNotification($order);

        return redirect()->route('order.qris', [$tablenumber, $order->code]);
    }

    private function processGopayPayment($order, $params, $tablenumber)
    {
        $params["payment_type"] = "gopay";
        $params["gopay"] = [
            "enable_callback" => true,
            "callback_url" => url('/order/success/' . $tablenumber . '/' . $order->code)
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

        return redirect()->route('order.qris', [$tablenumber, $order->code]);
    }

    private function sendWebhookNotification($order)
    {
        try {
            Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post(env('N8N_WEBHOOK_URL'), [
                'order_id'   => $order->id,
                'order_code' => $order->code,
                'table_id'   => $order->table_id,
                'total'      => number_format($order->total_amount, 0, ',', '.'),
                'phone' => preg_replace('/^0/', '62', $order->customer_phone),
            ]);
        } catch (\Exception $e) {
            Log::error('Gagal kirim ke webhook n8n: ' . $e->getMessage());
        }
    }
    // Halaman QRIS (dummy)
    public function qris($tablenumber, $code)
    {
        $order = Order::where('code', $code)->firstOrFail();
        $table = Table::where('name', $tablenumber)->firstOrFail();
        return view('order.qris', compact('order', 'table'));
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

    public function qrisConfirm(Request $request, $tablenumber, $code)
    {
        $order = Order::where('code', $code)->firstOrFail();

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
                $order->completed_at = now();
                $order->save();
                
                // STOCK SUDAH DIKURANGI SAAT CHECKOUT - TIDAK PERLU KURANGI LAGI
                Log::info('QRIS/GoPay CONFIRM: Payment confirmed - stock already reserved at checkout', [
                    'order_code' => $order->code,
                    'status' => $order->status,
                    'note' => 'Stock was already reduced during checkout process'
                ]);
                
                // Kirim notifikasi ke user
                $this->sendNotification('1 New Order', 'New order received from table ' . $order->table->name);
                
                return redirect()->route('order.success', [$tablenumber, $code]);
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
    public function success($tablenumber, $code)
    {
        $order = Order::where('code', $code)->firstOrFail();
        $table = Table::where('name', $tablenumber)->firstOrFail();
        return view('order.success', compact('order', 'table'));
    }

    public function midtransCallback(Request $request)
    {
        $notif = new Notification();

        $orderId = $notif->order_id;
        $transaction = $notif->transaction_status;

        $order = Order::where('code', $orderId)->first();
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        if ($transaction == 'capture' || $transaction == 'settlement') {
            $order->status = 'paid';
            $order->completed_at = now();
            //send notification to user
            // $this->sendNotification($order->table->user_id, '1 New Order', 'New order received from table ' . $order->table->name);
        } elseif ($transaction == 'cancel' || $transaction == 'expire') {
            $order->status = 'failed';
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

    public function detail($tableName, $productId)
    {
        $table = Table::where('name', $tableName)->firstOrFail();
        $product = Product::findOrFail($productId);
        return view('order.product_detail', compact('table', 'product'));
    }

    // Tambah ke keranjang (session) dari detail page
    public function addToCartWithNote(Request $request, $tableName, $productId)
    {
        try {
            $table = Table::where('name', $tableName)->firstOrFail();
            $product = Product::findOrFail($productId);
            $qtyToAdd = (int) $request->input('qty', 1);

            // Validasi stock real-time dengan locking
            $errors = $this->validateStockAvailability([
                ['product_id' => $product->id, 'quantity' => $qtyToAdd]
            ]);

            if (!empty($errors)) {
                return redirect()
                    ->route('order.detail', [$table->name, $product->id])
                    ->with('error', $errors[0]);
            }

            $cartKey = 'cart_' . $table->name;
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
                    ->route('order.detail', [$table->name, $product->id])
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
                ->route('order.detail', [$table->name, $product->id])
                ->with('success', "Added {$qtyToAdd} {$product->name} to cart");

        } catch (\Exception $e) {
            Log::error('Error adding to cart with note', [
                'product_id' => $productId,
                'table' => $tableName,
                'error' => $e->getMessage()
            ]);

            return redirect()
                ->route('order.detail', [$tableName, $productId])
                ->with('error', 'Failed to add item to cart. Please try again.');
        }
    }


    public function calculateTax($items)
    {
        // Get tax percentage from settings
        $taxPercentage = tax_percentage();
        
        // Calculate subtotal
        $subtotal = collect($items)->sum(fn($item) => $item['price'] * $item['qty']);
        
        // Calculate tax amount
        $taxAmount = round($subtotal * ($taxPercentage / 100),2 );
        
        // Set properties
        // $this->tax_percentage = $taxPercentage;
        // $this->tax_amount = round($taxAmount, 2);
        // $this->subtotal = $subtotal;
        $total_amount = $subtotal + $taxAmount;
        
        return [$total_amount, $taxAmount];
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
     * AJAX endpoint untuk check payment status
     */
    public function checkPaymentStatus(Request $request, $tablenumber, $code)
    {
        try {
            $order = Order::where('code', $code)->firstOrFail();
            
            // Jika order sudah paid di database, langsung return success
            if ($order->status === 'paid') {
                return response()->json([
                    'status' => 'paid',
                    'message' => 'Payment completed successfully',
                    'redirect_url' => route('order.success', [$tablenumber, $code])
                ]);
            }
            
            // Jika status masih pending, check ke Midtrans
            if ($order->status === 'pending') {
                try {
                    $midtransStatus = \Midtrans\Transaction::status($order->code);
                    
                    Log::info('AJAX Payment Status Check', [
                        'order_code' => $order->code,
                        'db_status' => $order->status,
                        'midtrans_status' => $midtransStatus->transaction_status ?? 'unknown'
                    ]);
                    
                    // Jika berhasil di Midtrans, update database
                    if (in_array($midtransStatus->transaction_status, ['settlement', 'capture'])) {
                        $order->status = 'paid';
                        $order->completed_at = now();
                        $order->save();
                        
                        Log::info('Payment status updated to paid via AJAX', [
                            'order_code' => $order->code
                        ]);
                        
                        return response()->json([
                            'status' => 'paid',
                            'message' => 'Payment completed successfully',
                            'redirect_url' => route('order.success', [$tablenumber, $code])
                        ]);
                    }
                    
                    // Jika gagal/expired
                    if (in_array($midtransStatus->transaction_status, ['expire', 'cancel', 'deny'])) {
                        $order->status = 'failed';
                        $order->save();
                        
                        return response()->json([
                            'status' => 'failed',
                            'message' => 'Payment failed or expired',
                            'redirect_url' => route('order.menu', $tablenumber)
                        ]);
                    }
                    
                } catch (\Exception $e) {
                    Log::warning('Error checking Midtrans status', [
                        'order_code' => $order->code,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // Default: masih pending
            return response()->json([
                'status' => 'pending',
                'message' => 'Payment is still pending',
                'order_status' => $order->status
            ]);
            
        } catch (\Exception $e) {
            Log::error('AJAX Payment Status Check Error', [
                'order_code' => $code,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to check payment status'
            ], 500);
        }
    }

    /**
     * DEBUG: Force payment success (manual trigger)
     */
    public function forcePaymentSuccess(Request $request, $tablenumber, $code)
    {
        if (!app()->environment(['local', 'development'])) {
            abort(404);
        }
        
        try {
            $order = Order::where('code', $code)->firstOrFail();
            $order->status = 'paid';
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
                'message' => 'Failed to force payment success'
            ], 500);
        }
    }
}
