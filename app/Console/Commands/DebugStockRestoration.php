<?php

namespace App\Console\Commands;

use App\Models\CancellationRequest;
use App\Models\Order;
use Illuminate\Console\Command;

class DebugStockRestoration extends Command
{
    protected $signature = 'debug:stock';

    protected $description = 'Debug stock restoration issues';

    public function handle()
    {
        $this->info('=== Checking Cancelled Orders and Stock ===');
        $this->newLine();

        $orders = Order::where('status', 'cancelled')
            ->with('product')
            ->latest()
            ->limit(5)
            ->get();

        if ($orders->isEmpty()) {
            $this->warn('No cancelled orders found.');

            return;
        }

        foreach ($orders as $order) {
            $this->info("Order ID: {$order->id}");
            $this->line("  Product ID: {$order->product_id}");
            $this->line("  Product Name: {$order->product?->name}");
            $this->line("  Quantity: {$order->quantity}");
            $this->line("  Current Stock: {$order->product?->stock}");
            $this->line("  Order Status: {$order->status}");

            $cancellation = CancellationRequest::where('order_id', $order->id)->first();
            if ($cancellation) {
                $this->line("  Cancellation Status: {$cancellation->status}");
            }
            $this->newLine();
        }

        // Check all products
        $this->info('=== All Products Stock ===');
        $products = Order::distinct('product_id')->get('product_id');

        foreach ($products as $orderItem) {
            $product = $orderItem->product;
            if ($product) {
                $this->line("{$product->id}. {$product->name} - Stock: {$product->stock}");
            }
        }
    }
}
