<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DebugStockIssue extends Command
{
    protected $signature = 'debug:stock-issue';

    protected $description = 'Debug why stock is not being restored';

    public function handle()
    {
        $this->info('🔍 DEBUGGING STOCK RESTORATION ISSUE');
        $this->line('');

        // Step 1: Check if there are any cancelled orders
        $this->info('Step 1️⃣: Find cancelled orders');
        $cancelledOrders = Order::where('status', 'cancelled')
            ->with(['product', 'cancellationRequest'])
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();

        if ($cancelledOrders->isEmpty()) {
            $this->warn('⚠️ No cancelled orders found');

            return;
        }

        $this->info('Found '.$cancelledOrders->count()." cancelled order(s):\n");

        foreach ($cancelledOrders as $order) {
            $this->line("📦 Order ID: {$order->id}");
            $this->line("   Status: {$order->status}");
            $this->line("   Quantity: {$order->quantity}");
            $this->line("   Product ID: {$order->product_id}");
            $this->line("   Cancelled at: {$order->updated_at}");

            if ($order->product) {
                $this->line("   Current Stock: {$order->product->stock}");
                $this->line("   Product Name: {$order->product->name}");
            }

            if ($order->cancellationRequest) {
                $this->line("   Cancellation Status: {$order->cancellationRequest->status}");
            }
            $this->line('');
        }

        // Step 2: Get one cancelled order and trace what SHOULD happen
        $firstCancelledOrder = $cancelledOrders->first();
        $this->info('Step 2️⃣: Trace the last cancelled order');
        $this->line("Order ID: {$firstCancelledOrder->id}");

        // Check order transaction activities
        $activities = DB::table('order_transaction_activities')
            ->where('order_id', $firstCancelledOrder->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        if ($activities->count() > 0) {
            $this->info('Recent Activities:');
            foreach ($activities as $activity) {
                $this->line("  • {$activity->action} by user {$activity->actor_id} on {$activity->created_at}");
                if ($activity->metadata) {
                    $metadata = json_decode($activity->metadata, true);
                    if (isset($metadata['stock_restored'])) {
                        $restored = $metadata['stock_restored'];
                        $this->line('    Stock restoration:');
                        $this->line("    - Original: {$restored['original_stock']}");
                        $this->line("    - After restore: {$restored['new_stock']}");
                        $this->line("    - Quantity: {$restored['quantity_restored']}");
                    }
                }
            }
        }

        // Step 3: Check if stock matches what it should be
        $this->info('Step 3️⃣: Verify stock in database');
        $product = $firstCancelledOrder->product;
        if ($product) {
            // Get all orders for this product (not cancelled)
            $activeOrders = Order::where('product_id', $product->id)
                ->where('status', '!=', 'cancelled')
                ->sum('quantity');

            // Get cancelled orders
            $cancelledQty = Order::where('product_id', $product->id)
                ->where('status', 'cancelled')
                ->sum('quantity');

            $this->info("Product: {$product->name}");
            $this->line("  Current stock: {$product->stock}");
            $this->line("  Active orders qty: {$activeOrders}");
            $this->line("  Cancelled orders qty: {$cancelledQty}");
            $this->line('');

            // Suggestion
            $this->info('📝 Analysis:');
            $this->line('  Expected stock = active orders qty + cancelled qty + current stock');
            $this->line('  If stock is LOW: Stock was restored from cancelled orders ✓');
            $this->line('  If stock is HIGH: Stock was NOT properly restored ✗');
        }

        // Step 4: Check logs
        $this->info('Step 4️⃣: Check laravel.log for restoration logs');
        $logFile = storage_path('logs/laravel.log');
        if (file_exists($logFile)) {
            $logs = shell_exec("tail -n 50 \"{$logFile}\" | grep -i stock");
            if ($logs) {
                $this->info('Recent stock-related logs:');
                $this->line($logs);
            }
        }

        // Step 5: Show full debug SQL query
        $this->info('Step 5️⃣: SQL to check product stock manually:');
        $this->line('');
        $this->line("SELECT 
    p.id,
    p.name,
    p.stock,
    COUNT(DISTINCT CASE WHEN o.status != 'cancelled' THEN o.id END) as active_orders,
    SUM(CASE WHEN o.status != 'cancelled' THEN o.quantity ELSE 0 END) as reserved_qty,
    SUM(CASE WHEN o.status = 'cancelled' THEN o.quantity ELSE 0 END) as cancelled_qty
FROM products p
LEFT JOIN orders o ON p.id = o.product_id
WHERE p.id IN (SELECT product_id FROM orders WHERE status = 'cancelled' ORDER BY updated_at DESC LIMIT 5)
GROUP BY p.id;");

        $this->line('');

        // Step 6: Ask user what they see
        $this->info('Step 6️⃣: Frontend Issue?');
        $this->line('If database shows stock is RESTORED but FE shows decreased:');
        $this->line('  1. FE might be caching old data');
        $this->line('  2. FE needs to refetch product data after approval');
        $this->line('  3. Clear browser cache or localStorage');
        $this->line('');
        $this->line('Run this to test directly in API:');
        $this->line('  curl http://localhost:8000/api/products/[PRODUCT_ID]');
    }
}
