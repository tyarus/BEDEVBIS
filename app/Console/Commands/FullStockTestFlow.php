<?php

namespace App\Console\Commands;

use App\Models\CancellationRequest;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FullStockTestFlow extends Command
{
    protected $signature = 'test:full-stock-flow';

    protected $description = 'Test complete stock restoration flow with detailed output';

    public function handle()
    {
        $this->info('╔════════════════════════════════════════════════════════════╗');
        $this->info('║        FULL STOCK RESTORATION FLOW TEST                    ║');
        $this->info('╚════════════════════════════════════════════════════════════╝');
        $this->newLine();

        try {
            // Get test users
            $buyer = User::where('role', 'buyer')->first();
            $product = Product::where('stock', '>', 0)->first();

            if (! $buyer || ! $product) {
                $this->error('❌ Test data tidak lengkap. Perlu buyer dan product dengan stock > 0');

                return;
            }

            $this->info("📦 Product: {$product->name}");
            $this->info("👤 Buyer: {$buyer->name}");
            $this->newLine();

            // ==================== STEP 1: CHECK INITIAL STOCK ====================
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->info('STEP 1: Initial Stock Check');
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

            $initialStock = $product->stock;
            $this->line("Initial Stock: <fg=cyan>{$initialStock}</>");
            $this->newLine();

            // ==================== STEP 2: CREATE ORDER ====================
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->info('STEP 2: Creating Order');
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

            $quantity = 2;
            $order = Order::create([
                'buyer_id' => $buyer->id,
                'product_id' => $product->id,
                'seller_id' => $product->seller_id,
                'quantity' => $quantity,
                'total_price' => $product->price * $quantity,
                'status' => 'pending_payment',
            ]);

            // Update stock using same method as controller
            DB::table('products')
                ->where('id', $product->id)
                ->update([
                    'stock' => $initialStock - $quantity,
                    'updated_at' => now(),
                ]);

            $product->refresh();
            $this->line("Order ID: <fg=cyan>#{$order->id}</>");
            $this->line("Quantity: <fg=cyan>{$quantity}</>");
            $this->line("Stock After Order: <fg=red>{$product->stock}</> (decreased by {$quantity})");
            $this->newLine();

            // ==================== STEP 3: CREATE CANCELLATION ====================
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->info('STEP 3: Creating Cancellation Request');
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

            $cancellation = CancellationRequest::create([
                'order_id' => $order->id,
                'buyer_id' => $buyer->id,
                'seller_id' => $product->seller_id,
                'reason' => 'other',
                'details' => 'Test cancellation flow',
                'status' => 'pending',
            ]);

            $this->line("Cancellation ID: <fg=cyan>#{$cancellation->id}</>");
            $this->line("Status: <fg=yellow>{$cancellation->status}</>");
            $this->newLine();

            // ==================== STEP 4: APPROVE CANCELLATION ====================
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->info('STEP 4: Approving Cancellation (Stock Restoration)');
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

            // Get pre-approval stock
            $preApprovalStock = $product->stock;

            // Approve using same method as controller
            $cancellation->update(['status' => 'approved']);
            $order->update(['status' => 'cancelled']);

            // Restore stock
            $newStock = $preApprovalStock + $quantity;
            DB::table('products')
                ->where('id', $product->id)
                ->update([
                    'stock' => $newStock,
                    'status' => 'active',
                    'updated_at' => now(),
                ]);

            $product->refresh();
            $this->line('Cancellation Approved: <fg=green>✓</>');
            $this->line("Stock Before Restoration: <fg=yellow>{$preApprovalStock}</>");
            $this->line("Stock After Restoration: <fg=green>{$product->stock}</>");
            $this->line("Quantity Restored: <fg=green>+{$quantity}</>");
            $this->newLine();

            // ==================== STEP 5: FINAL VERIFICATION ====================
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->info('STEP 5: Final Verification');
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

            $finalStock = $product->stock;
            $stockRestored = ($finalStock === $initialStock);

            $this->line("Initial Stock: <fg=cyan>{$initialStock}</>");
            $this->line("Final Stock: <fg=cyan>{$finalStock}</>");
            $this->line('Status: '.($stockRestored ? '<fg=green>✓ STOCK RESTORED CORRECTLY</>' : '<fg=red>✗ STOCK NOT RESTORED</>'));
            $this->newLine();

            // ==================== SUMMARY ====================
            $this->info('╔════════════════════════════════════════════════════════════╗');
            $this->info('║                        SUMMARY                            ║');
            $this->info('╚════════════════════════════════════════════════════════════╝');

            $this->table(
                ['Step', 'Status', 'Stock'],
                [
                    ['1. Initial', '✓', $initialStock],
                    ['2. Order Created', '✓', $initialStock - $quantity],
                    ['3. Cancellation', '✓', $initialStock - $quantity],
                    ['4. Approved', '✓', $newStock],
                    ['5. Final', $stockRestored ? '✓' : '✗', $finalStock],
                ]
            );
            $this->newLine();

            if ($stockRestored) {
                $this->info('<fg=green;bg=black>✓ ALL TESTS PASSED! Stock restoration is working correctly!</>');
            } else {
                $this->error('✗ TEST FAILED! Stock was not restored properly.');
            }

            // Cleanup test data
            $this->info('Cleaning up test data...');
            $order->delete();
            $cancellation->delete();
            $this->info('<fg=green>✓ Cleanup complete</>');

        } catch (\Exception $e) {
            $this->error('❌ Error during test: '.$e->getMessage());
            $this->error($e->getTraceAsString());
        }
    }
}
