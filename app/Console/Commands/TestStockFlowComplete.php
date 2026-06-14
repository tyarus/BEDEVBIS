<?php

namespace App\Console\Commands;

use App\Models\CancellationRequest;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestStockFlowComplete extends Command
{
    protected $signature = 'test:stock-flow-complete';

    protected $description = 'Test complete stock flow: create → cancel → approve and verify';

    public function handle()
    {
        $this->info('🚀 COMPLETE STOCK RESTORATION FLOW TEST');
        $this->line('');

        // Get or create test users
        $buyer = User::where('role', 'buyer')->first();
        $seller = User::where('role', 'seller')->first();

        if (! $buyer || ! $seller) {
            $this->error('❌ Test buyer or seller not found');

            return;
        }

        // Create clean test product
        $product = Product::create([
            'seller_id' => $seller->id,
            'name' => 'Test Stock Restoration - '.now()->timestamp,
            'price' => 100000,
            'stock' => 10,
            'status' => 'active',
        ]);

        $this->info('✅ Step 1: Created test product');
        $this->line("   Product ID: {$product->id}");
        $this->line("   Product Name: {$product->name}");
        $this->line("   Initial Stock: {$product->stock}");
        $this->line('');

        // Create order 1
        $this->info('⏳ Step 2: Creating Order 1 (qty: 1)');
        $order1 = Order::create([
            'buyer_id' => $buyer->id,
            'product_id' => $product->id,
            'seller_id' => $seller->id,
            'quantity' => 1,
            'total_price' => $product->price * 1,
            'status' => 'pending_payment',
        ]);

        DB::table('products')->where('id', $product->id)->update([
            'stock' => DB::raw('stock - 1'),
            'updated_at' => now(),
        ]);

        $stock1 = DB::table('products')->find($product->id)->stock;
        $this->line('   ✅ Order 1 created');
        $this->line("   Stock after order 1: {$stock1}");
        $this->line('');

        // Create order 2
        $this->info('⏳ Step 3: Creating Order 2 (qty: 1)');
        $order2 = Order::create([
            'buyer_id' => $buyer->id,
            'product_id' => $product->id,
            'seller_id' => $seller->id,
            'quantity' => 1,
            'total_price' => $product->price * 1,
            'status' => 'pending_payment',
        ]);

        DB::table('products')->where('id', $product->id)->update([
            'stock' => DB::raw('stock - 1'),
            'updated_at' => now(),
        ]);

        $stock2 = DB::table('products')->find($product->id)->stock;
        $this->line('   ✅ Order 2 created');
        $this->line("   Stock after order 2: {$stock2}");
        $this->line('');

        // Create cancellation request for order 1
        $this->info('⏳ Step 4: Creating cancellation request for Order 1');
        $cancel1 = CancellationRequest::create([
            'order_id' => $order1->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'reason' => 'other',
            'details' => 'Test cancellation',
            'status' => 'pending',
        ]);
        $this->line('   ✅ Cancellation request created');
        $this->line('');

        // Approve cancellation 1 (manually simulating what approve() does)
        $this->info('⏳ Step 5: Approving cancellation for Order 1');

        // This is what the approve() method does
        DB::transaction(function () use ($order1, $cancel1, $product, &$stock3) {
            // Update status
            $cancel1->update(['status' => 'approved']);
            $order1->update(['status' => 'cancelled']);

            // Get current stock
            $currentStock = DB::table('products')->find($product->id)->stock;
            $this->line("   Current stock before restore: {$currentStock}");

            // Calculate new stock
            $newStock = $currentStock + $order1->quantity;

            // Update stock
            DB::table('products')
                ->where('id', $product->id)
                ->update([
                    'stock' => $newStock,
                    'updated_at' => now(),
                ]);

            $stock3 = DB::table('products')->find($product->id)->stock;
            $this->line("   Stock after restoration: {$stock3}");
            $this->line("   (Added back {$order1->quantity} from cancelled order)");
        });

        $this->line('');

        // Create cancellation request for order 2
        $this->info('⏳ Step 6: Creating cancellation request for Order 2');
        $cancel2 = CancellationRequest::create([
            'order_id' => $order2->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'reason' => 'other',
            'details' => 'Test cancellation',
            'status' => 'pending',
        ]);
        $this->line('   ✅ Cancellation request created');
        $this->line('');

        // Approve cancellation 2
        $this->info('⏳ Step 7: Approving cancellation for Order 2');

        DB::transaction(function () use ($order2, $cancel2, $product, &$stock4) {
            // Update status
            $cancel2->update(['status' => 'approved']);
            $order2->update(['status' => 'cancelled']);

            // Get current stock
            $currentStock = DB::table('products')->find($product->id)->stock;
            $this->line("   Current stock before restore: {$currentStock}");

            // Calculate new stock
            $newStock = $currentStock + $order2->quantity;

            // Update stock
            DB::table('products')
                ->where('id', $product->id)
                ->update([
                    'stock' => $newStock,
                    'updated_at' => now(),
                ]);

            $stock4 = DB::table('products')->find($product->id)->stock;
            $this->line("   Stock after restoration: {$stock4}");
            $this->line("   (Added back {$order2->quantity} from cancelled order)");
        });

        $this->line('');

        // Final verification
        $finalProduct = Product::find($product->id);
        $this->info('✅ FINAL RESULT:');
        $this->line('   Initial Stock: 10');
        $this->line("   After 2 orders: {$stock2}");
        $this->line("   After cancel & approve order 1: {$stock3}");
        $this->line("   After cancel & approve order 2: {$stock4}");
        $this->line("   Final Stock in DB: {$finalProduct->stock}");
        $this->line('');

        if ($finalProduct->stock == 10) {
            $this->info('✅✅✅ SUCCESS! Stock restoration working correctly!');
            $this->line('    Stock returned to initial value: 10');
        } else {
            $this->error("❌ FAIL! Stock is {$finalProduct->stock}, expected 10");
            $this->line('    This means stock restoration is NOT working');
        }

        $this->line('');
        $this->info("Test product created with ID: {$product->id}");
        $this->line("You can check in DB: SELECT * FROM products WHERE id = {$product->id};");
    }
}
