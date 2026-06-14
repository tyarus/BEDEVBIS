<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckCancellationStatus extends Command
{
    protected $signature = 'debug:check-cancellation {product_id=12}';

    protected $description = 'Check cancellation request status for product orders';

    public function handle()
    {
        $productId = $this->argument('product_id');

        $this->info("📋 Checking cancellation requests for product {$productId}");
        $this->line('');

        // Get all orders for this product with cancellation requests
        $orders = DB::table('orders')
            ->leftJoin('cancellation_requests', 'orders.id', '=', 'cancellation_requests.order_id')
            ->where('orders.product_id', $productId)
            ->select(
                'orders.id',
                'orders.quantity',
                'orders.status as order_status',
                'cancellation_requests.id as cancel_id',
                'cancellation_requests.status as cancel_status',
                'cancellation_requests.created_at as cancel_created'
            )
            ->orderBy('orders.id', 'desc')
            ->get();

        if ($orders->isEmpty()) {
            $this->error('No orders found for this product');

            return;
        }

        $this->info('Orders and their cancellation status:');
        $this->line('');

        $pending_cancelled = 0;
        $approved_cancelled = 0;
        $no_cancellation = 0;

        foreach ($orders as $order) {
            $cancelId = $order->cancel_id ? "#{$order->cancel_id}" : 'NONE';
            $cancelStatus = $order->cancel_status ?? 'N/A';

            $icon = match ($order->order_status) {
                'cancelled' => '❌',
                'pending_payment' => '⏳',
                'paid' => '✓',
                default => '○'
            };

            $this->line("{$icon} Order #{$order->id}: qty={$order->quantity}, status={$order->order_status}");

            if ($order->cancel_id) {
                if ($order->cancel_status === 'pending') {
                    $this->line("   └─ Cancellation {$cancelId}: ⏳ PENDING (NOT APPROVED YET!)");
                    $pending_cancelled++;
                } elseif ($order->cancel_status === 'approved') {
                    $this->line("   └─ Cancellation {$cancelId}: ✅ APPROVED");
                    $approved_cancelled++;
                } elseif ($order->cancel_status === 'rejected') {
                    $this->line("   └─ Cancellation {$cancelId}: ✗ REJECTED");
                } else {
                    $this->line("   └─ Cancellation {$cancelId}: {$cancelStatus}");
                }
            } else {
                $this->line('   └─ No cancellation request');
                $no_cancellation++;
            }
        }

        $this->line('');
        $this->info('📊 Summary:');
        $this->line("   Pending cancellations: {$pending_cancelled} (⚠️ These need approval!)");
        $this->line("   Approved cancellations: {$approved_cancelled} (✅ Stock should be restored)");
        $this->line("   No cancellation: {$no_cancellation}");

        if ($pending_cancelled > 0) {
            $this->warn('');
            $this->warn("⚠️ FOUND {$pending_cancelled} PENDING CANCELLATIONS!");
            $this->line("   These orders' stock is NOT restored yet.");
            $this->line('   You must APPROVE them as seller to restore stock.');
        }
    }
}
