<?php

namespace App\Console\Commands;

use App\Models\CancellationRequest;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestStockRestoration extends Command
{
    protected $signature = 'test:stock-restoration';

    protected $description = 'Test stock restoration functionality';

    public function handle()
    {
        $this->info('=== Testing Stock Restoration ===');
        $this->newLine();

        // Find any order to test
        $product = Product::first();
        if (! $product) {
            $this->error('No products found!');

            return;
        }

        $this->info("Test Product: {$product->name}");
        $this->line("Product ID: {$product->id}");
        $this->line("Current Stock: {$product->stock}");
        $this->newLine();

        // Create test order
        $this->info('Creating test order...');
        $order = Order::create([
            'buyer_id' => 1,
            'product_id' => $product->id,
            'seller_id' => $product->seller_id,
            'quantity' => 2,
            'total_price' => $product->price * 2,
            'status' => 'pending_payment',
        ]);

        // Refresh product to see new stock
        $product->refresh();
        $this->line("Stock after order creation: {$product->stock}");
        $this->newLine();

        // Create cancellation request
        $this->info('Creating cancellation request...');
        $cancellation = CancellationRequest::create([
            'order_id' => $order->id,
            'buyer_id' => 1,
            'seller_id' => $product->seller_id,
            'reason' => 'other',
            'details' => 'Testing stock restoration',
            'status' => 'pending',
        ]);

        // Simulate approval by updating status
        $this->info('Simulating seller approval...');
        $cancellation->update(['status' => 'approved']);
        $order->update(['status' => 'cancelled']);

        // Manually restore stock using same logic as controller
        $originalStock = $product->stock;
        $newStock = $originalStock + $order->quantity;

        DB::table('products')
            ->where('id', $product->id)
            ->update([
                'stock' => $newStock,
                'status' => 'active',
                'updated_at' => now(),
            ]);

        // Refresh and check
        $product->refresh();
        $this->line("Stock after approval: {$product->stock}");
        $this->newLine();

        if ($product->stock == $newStock) {
            $this->info('✓ Stock restoration successful!');
        } else {
            $this->error('✗ Stock restoration failed!');
            $this->line("Expected: {$newStock}, Got: {$product->stock}");
        }

        // Cleanup
        $this->info('Cleaning up test data...');
        $order->delete();
        $cancellation->delete();
    }
}
