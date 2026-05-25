<?php

namespace App\Services;

use App\Models\User;
use App\Models\WalletAccount;
use App\Models\WalletEscrow;
use App\Models\WalletLedgerEntry;
use App\Models\WalletWithdrawal;
use Illuminate\Support\Facades\DB;

class WalletService
{
    /**
     * Get or create wallet account for user
     */
    public function getOrCreateWalletAccount(User $user): WalletAccount
    {
        return WalletAccount::firstOrCreate(
            ['user_id' => $user->id],
            [
                'available_balance' => 0,
                'total_topup' => 0,
                'total_sales' => 0,
                'total_withdraw' => 0,
                'total_refund' => 0,
            ]
        );
    }

    /**
     * Process top up for buyer
     */
    public function processTopup(User $buyer, int $amount): WalletAccount
    {
        return DB::transaction(function () use ($buyer, $amount) {
            $wallet = $this->getOrCreateWalletAccount($buyer);

            // Update wallet
            $newBalance = $wallet->available_balance + $amount;
            $wallet->update([
                'available_balance' => $newBalance,
                'total_topup' => $wallet->total_topup + $amount,
            ]);

            // Record in ledger
            WalletLedgerEntry::create([
                'user_id' => $buyer->id,
                'order_id' => null,
                'type' => 'topup',
                'direction' => 'credit',
                'amount' => $amount,
                'balance_after' => $newBalance,
                'description' => 'Top up saldo simulasi',
                'metadata' => null,
                'created_at' => now(),
            ]);

            return $wallet->fresh();
        });
    }

    /**
     * Hold funds for order (buyer pays)
     */
    public function holdFundsForOrder(User $buyer, User $seller, int $orderId, $amount): WalletEscrow
    {
        return DB::transaction(function () use ($buyer, $seller, $orderId, $amount) {
            $buyerWallet = $this->getOrCreateWalletAccount($buyer);

            // Convert amount to integer for consistent comparison
            $amountInt = (int) $amount;

            // Auto top-up if balance is insufficient (for development/testing)
            if ($buyerWallet->available_balance < $amountInt) {
                $topupAmount = $amountInt - $buyerWallet->available_balance;
                $buyerWallet->update([
                    'available_balance' => $amountInt,
                    'total_topup' => $buyerWallet->total_topup + $topupAmount,
                ]);

                // Record auto-topup in ledger
                WalletLedgerEntry::create([
                    'user_id' => $buyer->id,
                    'order_id' => null,
                    'type' => 'topup',
                    'direction' => 'credit',
                    'amount' => $topupAmount,
                    'balance_after' => $amountInt,
                    'description' => 'Auto top-up untuk pembayaran order',
                    'metadata' => null,
                    'created_at' => now(),
                ]);
            }

            // Deduct from buyer balance
            $buyerNewBalance = $buyerWallet->available_balance - $amountInt;
            $buyerWallet->update([
                'available_balance' => $buyerNewBalance,
            ]);

            // Record in buyer ledger (debit)
            WalletLedgerEntry::create([
                'user_id' => $buyer->id,
                'order_id' => $orderId,
                'type' => 'order_hold',
                'direction' => 'debit',
                'amount' => $amountInt,
                'balance_after' => $buyerNewBalance,
                'description' => 'Dana order ditahan di escrow',
                'metadata' => null,
                'created_at' => now(),
            ]);

            // Create escrow record
            $escrow = WalletEscrow::create([
                'order_id' => $orderId,
                'buyer_id' => $buyer->id,
                'seller_id' => $seller->id,
                'amount' => $amountInt,
                'status' => 'held',
            ]);

            return $escrow;
        });
    }

    /**
     * Release funds from escrow to seller
     */
    public function releaseFundsToSeller(WalletEscrow $escrow): WalletEscrow
    {
        return DB::transaction(function () use ($escrow) {
            // Validate escrow status
            if ($escrow->status !== 'held') {
                throw new \Exception('Status escrow tidak valid untuk dirilis', 409);
            }

            $seller = $escrow->seller;
            $sellerWallet = $this->getOrCreateWalletAccount($seller);

            // Add to seller balance
            $sellerNewBalance = $sellerWallet->available_balance + $escrow->amount;
            $sellerWallet->update([
                'available_balance' => $sellerNewBalance,
                'total_sales' => $sellerWallet->total_sales + $escrow->amount,
            ]);

            // Record in seller ledger (credit)
            WalletLedgerEntry::create([
                'user_id' => $seller->id,
                'order_id' => $escrow->order_id,
                'type' => 'order_release',
                'direction' => 'credit',
                'amount' => $escrow->amount,
                'balance_after' => $sellerNewBalance,
                'description' => 'Dana order dirilis dari escrow',
                'metadata' => null,
                'created_at' => now(),
            ]);

            // Update escrow status
            $escrow->update([
                'status' => 'released',
                'released_at' => now(),
            ]);

            return $escrow->fresh();
        });
    }

    /**
     * Refund funds from escrow back to buyer
     */
    public function refundFundsToBuyer(WalletEscrow $escrow): WalletEscrow
    {
        return DB::transaction(function () use ($escrow) {
            // Validate escrow status
            if ($escrow->status !== 'held') {
                throw new \Exception('Status escrow tidak valid untuk dikembalikan', 409);
            }

            $buyer = $escrow->buyer;
            $buyerWallet = $this->getOrCreateWalletAccount($buyer);

            // Add back to buyer balance
            $buyerNewBalance = $buyerWallet->available_balance + $escrow->amount;
            $buyerWallet->update([
                'available_balance' => $buyerNewBalance,
                'total_refund' => $buyerWallet->total_refund + $escrow->amount,
            ]);

            // Record in buyer ledger (credit)
            WalletLedgerEntry::create([
                'user_id' => $buyer->id,
                'order_id' => $escrow->order_id,
                'type' => 'order_refund',
                'direction' => 'credit',
                'amount' => $escrow->amount,
                'balance_after' => $buyerNewBalance,
                'description' => 'Dana order dikembalikan dari escrow',
                'metadata' => null,
                'created_at' => now(),
            ]);

            // Update escrow status
            $escrow->update([
                'status' => 'refunded',
                'refunded_at' => now(),
            ]);

            return $escrow->fresh();
        });
    }

    /**
     * Process withdrawal for seller
     */
    public function processWithdrawal(User $seller, int $amount, string $bankName, string $accountName, string $accountNumber): WalletWithdrawal
    {
        return DB::transaction(function () use ($seller, $amount, $bankName, $accountName, $accountNumber) {
            $wallet = $this->getOrCreateWalletAccount($seller);

            // Validate seller has enough balance
            if ($wallet->available_balance < $amount) {
                throw new \Exception('Saldo tidak cukup untuk melakukan withdraw', 409);
            }

            // Deduct from seller balance
            $newBalance = $wallet->available_balance - $amount;
            $wallet->update([
                'available_balance' => $newBalance,
                'total_withdraw' => $wallet->total_withdraw + $amount,
            ]);

            // Record in ledger
            WalletLedgerEntry::create([
                'user_id' => $seller->id,
                'order_id' => null,
                'type' => 'withdraw',
                'direction' => 'debit',
                'amount' => $amount,
                'balance_after' => $newBalance,
                'description' => 'Withdraw dana ke rekening bank',
                'metadata' => null,
                'created_at' => now(),
            ]);

            // Generate reference number
            $referenceNumber = 'WD-' . now()->format('YmdHis') . '-' . str_pad($seller->id, 6, '0', STR_PAD_LEFT);

            // Create withdrawal record
            $withdrawal = WalletWithdrawal::create([
                'seller_id' => $seller->id,
                'amount' => $amount,
                'bank_name' => $bankName,
                'account_name' => $accountName,
                'account_number' => $accountNumber,
                'reference_number' => $referenceNumber,
                'metadata' => null,
            ]);

            return $withdrawal;
        });
    }

    /**
     * Get wallet summary
     */
    public function getWalletSummary(User $user): array
    {
        $wallet = $this->getOrCreateWalletAccount($user);

        // Calculate held amounts
        $heldAsBuyer = WalletEscrow::where('buyer_id', $user->id)
            ->where('status', 'held')
            ->sum('amount');

        $heldAsSeller = WalletEscrow::where('seller_id', $user->id)
            ->where('status', 'held')
            ->sum('amount');

        return [
            'available_balance' => $wallet->available_balance,
            'held_amount_as_buyer' => $heldAsBuyer,
            'held_amount_as_seller' => $heldAsSeller,
            'total_topup' => $wallet->total_topup,
            'total_sales' => $wallet->total_sales,
            'total_withdraw' => $wallet->total_withdraw,
            'total_refund' => $wallet->total_refund,
        ];
    }
}
