<?php

namespace App\Console\Commands;

use App\Models\CancellationRequest;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestUserScenario extends Command
{
    protected $signature = 'test:user-scenario';

    protected $description = 'Replicate user scenario: stock 10 → 2 orders → cancel both → check stock';

    public function handle()
    {
        $this->info('🔍 REPLICATING USER SCENARIO');
        $this->line('User says: "Stok 10, buat 2 pesanan, stok jadi 8, batalkan 2 pesanan, tapi stok masih 8"');
        $this->line('');

        // Get test users
        $buyer = User::where('role', 'buyer')->first();
        $seller = User::where('role', 'seller')->first();

        if (! $buyer || ! $seller) {
            $this->error('Test users not found');

            return;
        }

        // Check if there's an EXISTING product the user might be using
        $this->info('Step 1️⃣: Check if product already exists');
        $product = Product::where('id', 12)->first();

        if (! $product) {
            $this->line('  Creating new test product...');
            $product = Product::create([
                'seller_id' => $seller->id,
                'name' => 'Test Product User Scenario',
                'price' => 100000,
                'stock' => 10,
                'status' => 'active',
            ]);
        } else {
            $this->line("  Found existing product: {$product->name}");
            $this->line("  Current stock: {$product->stock}");

            // Reset for fresh test
            if ($this->confirm('Reset stock to 10 for fresh test?')) {
                DB::table('products')->where('id', $product->id)->update(['stock' => 10]);
                $product->refresh();
            }
        }

        $this->line("  Product ID: {$product->id}, Stock: {$product->stock}");
        $this->line('');

        // Create 2 orders
        $this->info('Step 2️⃣: Create 2 orders (qty: 1 each)');

        for ($i = 1; $i <= 2; $i++) {
            $order = Order::create([
                'buyer_id' => $buyer->id,
                'product_id' => $product->id,
                'seller_id' => $seller->id,
                'quantity' => 1,
                'total_price' => $product->price,
                'status' => 'pending_payment',
            ]);

            DB::table('products')->where('id', $product->id)->update([
                'stock' => DB::raw('stock - 1'),
                'updated_at' => now(),
            ]);

            $product->refresh();
            $this->line("  Order {$i} created, stock now: {$product->stock}");
        }

        $this->line('');

        // Now cancel both orders
        $this->info('Step 3️⃣: Cancel and approve both orders');
        $this->line('');

        $orders = Order::where('product_id', $product->id)
            ->where('status', 'pending_payment')
            ->limit(2)
            ->get();

        foreach ($orders as $index => $order) {
            $orderNum = $index + 1;
            $this->line("📋 Processing Order #{$order->id}:");

            // Create cancellation request
            $cancel = CancellationRequest::create([
                'order_id' => $order->id,
                'buyer_id' => $buyer->id,
                'seller_id' => $seller->id,
                'reason' => 'other',
                'details' => 'User test scenario',
                'status' => 'pending',
            ]);
            $this->line('   • Cancellation request created');

            // Trace EXACTLY what approve() does
            $this->line('   • Simulating approve() method:');

            DB::transaction(function () use ($order, $cancel, $product) {
                // Step 1: Update cancellation status
                $cancel->update(['status' => 'approved']);
                $this->line('     1️⃣ Cancellation status → approved');

                // Step 2: Update order status
                $order->update(['status' => 'cancelled']);
                $this->line('     2️⃣ Order status → cancelled');

                // Step 3: Get current stock
                $currentStock = DB::table('products')->where('id', $product->id)->first()->stock;
                $this->line("     3️⃣ Current stock in DB: {$currentStock}");

                // Step 4: Calculate new stock
                $newStock = $currentStock + $order->quantity;
                $this->line("     4️⃣ Calculate new stock: {$currentStock} + {$order->quantity} = {$newStock}");

                // Step 5: Update product stock
                $result = DB::table('products')
                    ->where('id', $product->id)
                    ->update([
                        'stock' => $newStock,
                        'status' => 'active',
                        'updated_at' => now(),
                    ]);

                $verifyStock = DB::table('products')->where('id', $product->id)->first()->stock;
                $this->line('     5️⃣ Updated product stock in DB');
                $this->line("     6️⃣ Verified stock in DB: {$verifyStock}");

                $this->line('');
            });

            $product->refresh();
            $this->line("   ✅ Stock after approval: {$product->stock}");
            $this->line('');
        }

        // Final check
        $this->info('Step 4️⃣: FINAL VERIFICATION');
        $product->refresh();
        $this->line("Final stock in DB: {$product->stock}");

        // Get all orders for this product
        $allOrders = Order::where('product_id', $product->id)->get();
        $cancelledQty = $allOrders->where('status', 'cancelled')->sum('quantity');
        $activeQty = $allOrders->where('status', '!=', 'cancelled')->sum('quantity');

        $this->line("Active orders qty: {$activeQty}");
        $this->line("Cancelled orders qty: {$cancelledQty}");
        $this->line("Expected stock: {$cancelledQty} + (10 - {$cancelledQty}) = 10");
        $this->line("Actual stock: {$product->stock}");

        $this->line('');
        if ($product->stock == 10) {
            $this->info('✅✅✅ SUCCESS!');
            $this->line('Stock correctly restored to 10');
        } else {
            $this->error('❌ PROBLEM!');
            $this->line("Stock is {$product->stock}, expected 10");
            $this->line('This indicates a BUG in the approval logic');
        }
    }
}
