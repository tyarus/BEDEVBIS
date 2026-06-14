<?php

use App\Models\CancellationRequest;
use App\Models\Order;
use App\Models\Product;

// Find cancelled orders
echo "=== Checking Cancelled Orders and Stock ===\n\n";

$orders = Order::where('status', 'cancelled')
    ->with('product')
    ->latest()
    ->limit(5)
    ->get();

if ($orders->isEmpty()) {
    echo "No cancelled orders found.\n";
} else {
    foreach ($orders as $order) {
        echo "Order ID: {$order->id}\n";
        echo "  Product ID: {$order->product_id}\n";
        echo "  Product Name: {$order->product?->name}\n";
        echo "  Quantity: {$order->quantity}\n";
        echo "  Current Stock: {$order->product?->stock}\n";
        echo "  Order Status: {$order->status}\n";

        $cancellation = CancellationRequest::where('order_id', $order->id)->first();
        if ($cancellation) {
            echo "  Cancellation Status: {$cancellation->status}\n";
        }
        echo "\n";
    }
}

// Also check all products
echo "\n=== All Products Stock ===\n";
$products = Product::all();
foreach ($products as $product) {
    echo "{$product->id}. {$product->name} - Stock: {$product->stock}\n";
}
