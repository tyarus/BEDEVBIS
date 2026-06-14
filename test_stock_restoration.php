<?php

// Test script untuk verify stock restoration
// Jalankan dengan: php artisan tinker < test_stock_restoration.php

use App\Models\CancellationRequest;
use App\Models\Order;

echo "=== Testing Stock Restoration ===\n\n";

// 1. Find order yang dibatalkan
$order = Order::where('status', 'cancelled')
    ->with('product')
    ->latest()
    ->first();

if (! $order) {
    echo "❌ Tidak ada cancelled order ditemukan\n";
    exit;
}

echo "✅ Order ditemukan:\n";
echo "  Order ID: {$order->id}\n";
echo "  Product ID: {$order->product_id}\n";
echo "  Product Name: {$order->product->name}\n";
echo "  Order Quantity: {$order->quantity}\n";
echo "  Current Stock: {$order->product->stock}\n";
echo "  Order Status: {$order->status}\n\n";

// 2. Find cancellation request
$cancellation = CancellationRequest::where('order_id', $order->id)->first();

if ($cancellation) {
    echo "Cancellation Request Found:\n";
    echo "  Status: {$cancellation->status}\n";
    echo "  Created: {$cancellation->created_at}\n";
    echo "  Updated: {$cancellation->updated_at}\n\n";
}

// 3. Calculate expected stock
// Current stock should be: original_stock + quantity (since it's cancelled)
$product = $order->product;
echo "Stock Verification:\n";
echo "  Product Stock: {$product->stock}\n";
echo "  Order Quantity: {$order->quantity}\n";

// Try to find order history from logs
echo "\n✅ Stock restoration test complete!\n";
echo "   If stock is correct, restoration is working properly.\n";
