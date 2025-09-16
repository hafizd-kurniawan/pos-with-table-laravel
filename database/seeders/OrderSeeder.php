<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Table;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tables = Table::limit(5)->get();
        $products = Product::limit(10)->get();
        $users = User::all();

        if ($tables->isEmpty() || $products->isEmpty() || $users->isEmpty()) {
            $this->command->warn('⚠️  Skipping OrderSeeder: Tables, Products, or Users not found');
            return;
        }

        // Sample orders untuk testing
        $sampleOrders = [
            [
                'table' => $tables->first(),
                'items' => [
                    ['product' => $products->get(0), 'quantity' => 2],
                    ['product' => $products->get(1), 'quantity' => 1],
                ],
                'status' => 'complete',
                'payment_status' => 'paid',
                'created_at' => Carbon::now()->subHours(2),
            ],
            [
                'table' => $tables->get(1),
                'items' => [
                    ['product' => $products->get(2), 'quantity' => 1],
                    ['product' => $products->get(3), 'quantity' => 3],
                    ['product' => $products->get(4), 'quantity' => 2],
                ],
                'status' => 'pending',
                'payment_status' => 'pending',
                'created_at' => Carbon::now()->subMinutes(30),
            ],
            [
                'table' => $tables->get(2),
                'items' => [
                    ['product' => $products->get(5), 'quantity' => 1],
                ],
                'status' => 'complete',
                'payment_status' => 'paid',
                'created_at' => Carbon::now()->subHours(5),
            ],
        ];

        foreach ($sampleOrders as $orderData) {
            // Calculate totals
            $subtotal = 0;
            foreach ($orderData['items'] as $item) {
                $price = (float) $item['product']->price;
                $subtotal += $price * $item['quantity'];
            }

            $tax = $subtotal * 0.11; // 11% tax
            $total = $subtotal + $tax;

            $order = Order::create([
                'table_id' => $orderData['table']->id,
                'total_amount' => $total,
                'payment_method' => $orderData['payment_status'] === 'paid' ? 'cash' : 'qris',
                'status' => $orderData['status'],
                'customer_name' => 'Customer ' . rand(1, 100),
                'customer_phone' => '08' . rand(1000000000, 9999999999),
                'notes' => 'Sample order for testing',
                'placed_at' => $orderData['created_at'],
                'completed_at' => $orderData['status'] === 'complete' ? $orderData['created_at']->addMinutes(30) : null,
                'created_at' => $orderData['created_at'],
                'updated_at' => $orderData['created_at'],
            ]);

            // Create order items
            foreach ($orderData['items'] as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product']->id,
                    'quantity' => $item['quantity'],
                    'price' => (float) $item['product']->price,
                    'total' => (float) $item['product']->price * $item['quantity'],
                ]);
            }

            // Update table status if order is pending
            if ($orderData['status'] === 'pending') {
                $orderData['table']->update([
                    'status' => 'occupied',
                    'order_id' => $order->id,
                    'occupied_at' => $orderData['created_at'],
                ]);
            }
        }

        $this->command->info("✅ Created " . count($sampleOrders) . " sample orders");
    }
}
