<?php

namespace Database\Seeders;

use App\Models\EscrowLog;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        EscrowLog::query()->truncate();
        Payment::query()->truncate();
        Order::query()->truncate();
        Product::query()->truncate();
        User::query()->truncate();
        Schema::enableForeignKeyConstraints();

        $buyer = User::create([
            'name' => 'tyar',
            'email' => 'tyar@example.com',
            'password' => 'password123',
            'role' => 'buyer',
        ]);

        $seller = User::create([
            'name' => 'tyars',
            'email' => 'tyars@example.com',
            'password' => 'password123',
            'role' => 'seller',
        ]);

        $products = collect([
            [
                'name' => 'Laptop Pro 14',
                'description' => 'Laptop untuk kerja dan editing.',
                'price' => 14500000,
                'stock' => 12,
                'image_url' => 'https://picsum.photos/seed/product-1/600/400',
                'status' => 'active',
            ],
            [
                'name' => 'Mechanical Keyboard',
                'description' => 'Keyboard switch tactile dengan RGB.',
                'price' => 1250000,
                'stock' => 25,
                'image_url' => 'https://picsum.photos/seed/product-2/600/400',
                'status' => 'active',
            ],
            [
                'name' => 'Wireless Mouse',
                'description' => 'Mouse ergonomis untuk penggunaan harian.',
                'price' => 450000,
                'stock' => 40,
                'image_url' => 'https://picsum.photos/seed/product-3/600/400',
                'status' => 'active',
            ],
            [
                'name' => '27-inch Monitor',
                'description' => 'Monitor IPS 2K untuk multitasking.',
                'price' => 3200000,
                'stock' => 8,
                'image_url' => 'https://picsum.photos/seed/product-4/600/400',
                'status' => 'active',
            ],
            [
                'name' => 'USB-C Docking Station',
                'description' => 'Docking station 8-in-1.',
                'price' => 950000,
                'stock' => 5,
                'image_url' => 'https://picsum.photos/seed/product-5/600/400',
                'status' => 'inactive',
            ],
        ])->map(function (array $product) use ($seller): Product {
            $product['seller_id'] = $seller->id;
            return Product::create($product);
        })->values();

        $orders = collect([
            ['product_index' => 0, 'quantity' => 1, 'status' => 'pending_payment', 'tracking_number' => null],
            ['product_index' => 1, 'quantity' => 2, 'status' => 'paid', 'tracking_number' => null],
            ['product_index' => 2, 'quantity' => 3, 'status' => 'processing', 'tracking_number' => null],
            ['product_index' => 3, 'quantity' => 1, 'status' => 'shipped', 'tracking_number' => 'TRX-SHIP-0004'],
            ['product_index' => 4, 'quantity' => 1, 'status' => 'completed', 'tracking_number' => 'TRX-DONE-0005'],
        ])->map(function (array $order) use ($products, $buyer, $seller): Order {
            $product = $products[$order['product_index']];
            return Order::create([
                'buyer_id' => $buyer->id,
                'product_id' => $product->id,
                'seller_id' => $seller->id,
                'quantity' => $order['quantity'],
                'total_price' => $product->price * $order['quantity'],
                'status' => $order['status'],
                'tracking_number' => $order['tracking_number'],
            ]);
        })->values();

        Payment::insert([
            [
                'order_id' => $orders[0]->id,
                'amount' => $orders[0]->total_price,
                'method' => 'bank_transfer',
                'status' => 'pending',
                'paid_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'order_id' => $orders[1]->id,
                'amount' => $orders[1]->total_price,
                'method' => 'virtual_account',
                'status' => 'success',
                'paid_at' => now()->subDays(3),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'order_id' => $orders[2]->id,
                'amount' => $orders[2]->total_price,
                'method' => 'ewallet',
                'status' => 'success',
                'paid_at' => now()->subDays(2),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'order_id' => $orders[3]->id,
                'amount' => $orders[3]->total_price,
                'method' => 'bank_transfer',
                'status' => 'success',
                'paid_at' => now()->subDay(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'order_id' => $orders[4]->id,
                'amount' => $orders[4]->total_price,
                'method' => 'virtual_account',
                'status' => 'success',
                'paid_at' => now()->subHours(12),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        EscrowLog::insert([
            [
                'order_id' => $orders[0]->id,
                'actor_id' => $buyer->id,
                'action' => 'order_created',
                'amount' => $orders[0]->total_price,
                'note' => 'Order dibuat dan menunggu pembayaran.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'order_id' => $orders[1]->id,
                'actor_id' => $buyer->id,
                'action' => 'payment_received',
                'amount' => $orders[1]->total_price,
                'note' => 'Pembayaran berhasil dan dana masuk escrow.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'order_id' => $orders[2]->id,
                'actor_id' => $seller->id,
                'action' => 'order_processing',
                'amount' => null,
                'note' => 'Seller menyiapkan pesanan.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'order_id' => $orders[3]->id,
                'actor_id' => $seller->id,
                'action' => 'order_shipped',
                'amount' => null,
                'note' => 'Pesanan dikirim dengan nomor resi.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'order_id' => $orders[4]->id,
                'actor_id' => $buyer->id,
                'action' => 'escrow_released',
                'amount' => $orders[4]->total_price,
                'note' => 'Pesanan dikonfirmasi selesai, dana dilepas ke seller.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
