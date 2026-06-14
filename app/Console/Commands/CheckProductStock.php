<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckProductStock extends Command
{
    protected $signature = 'debug:check-stock {product_id=12}';

    protected $description = 'Check product stock and orders';

    public function handle()
    {
        $productId = $this->argument('product_id');

        $product = DB::table('products')->find($productId);

        if (! $product) {
            $this->error("Product dengan ID {$productId} tidak ditemukan");

            return;
        }

        $this->info("📦 Product: {$product->name}");
        $this->line("ID: {$product->id}");
        $this->line("Current Stock in DB: {$product->stock}");
        $this->line('');

        // Get all orders
        $allOrders = DB::table('orders')
            ->where('product_id', $productId)
            ->select('id', 'quantity', 'status')
            ->get();

        $activeQty = $allOrders->where('status', '!=', 'cancelled')->sum('quantity');
        $cancelledQty = $allOrders->where('status', 'cancelled')->sum('quantity');

        $this->line('📋 Orders Analysis:');
        $this->line('  Total orders: '.$allOrders->count());
        $this->line("  Active orders qty: {$activeQty}");
        $this->line("  Cancelled orders qty: {$cancelledQty}");
        $this->line('');

        // Show detail
        $this->info('Order Details:');
        foreach ($allOrders as $order) {
            $status = $order->status === 'cancelled' ? '❌ ' : '✓ ';
            $this->line("  {$status}Order ID {$order->id}: qty={$order->quantity}, status={$order->status}");
        }

        $this->line('');
        $this->info('✅ Stock Check:');
        $this->line('  If Stock + Cancelled Orders > Active Orders Qty:');
        $this->line('    → Stock restoration is WORKING ✓');
        $this->line('');
        $this->line('  Your Numbers:');
        $this->line("    Stock: {$product->stock}");
        $this->line("    + Cancelled: {$cancelledQty}");
        $this->line('    = Total Available: '.($product->stock + $cancelledQty));
        $this->line("    - Active Orders: {$activeQty}");
        $this->line('    = Reserve: '.($product->stock + $cancelledQty - $activeQty));
    }
}
