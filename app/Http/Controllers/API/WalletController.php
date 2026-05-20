<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\TopupRequest;
use App\Http\Requests\WithdrawRequest;
use App\Http\Resources\WalletEscrowResource;
use App\Http\Resources\WalletLedgerEntryResource;
use App\Http\Resources\WalletResource;
use App\Http\Resources\WalletWithdrawalResource;
use App\Models\WalletEscrow;
use App\Models\WalletLedgerEntry;
use App\Models\WalletWithdrawal;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    private WalletService $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * GET /api/wallet/me
     * Get wallet information for authenticated user
     */
    public function getWallet(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $walletData = $this->walletService->getWalletSummary($user);

            return response()->json([
                'success' => true,
                'message' => 'Data wallet berhasil diambil',
                'data' => $walletData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * POST /api/wallet/topup
     * Top up simulation for buyer
     */
    public function topup(TopupRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $amount = $request->validated()['amount'];

            $wallet = $this->walletService->processTopup($user, $amount);

            return response()->json([
                'success' => true,
                'message' => 'Top up simulasi berhasil',
                'data' => [
                    'available_balance' => $wallet->available_balance,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 400);
        }
    }

    /**
     * GET /api/wallet/ledger
     * Get wallet ledger history
     */
    public function getLedger(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $ledgerEntries = WalletLedgerEntry::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Riwayat wallet berhasil diambil',
                'data' => WalletLedgerEntryResource::collection($ledgerEntries),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * GET /api/wallet/escrows
     * Get escrow records for user (as buyer or seller)
     */
    public function getEscrows(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Get escrows where user is buyer or seller
            $escrows = WalletEscrow::where(function ($query) use ($user) {
                $query->where('buyer_id', $user->id)
                    ->orWhere('seller_id', $user->id);
            })->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'message' => 'Data escrow berhasil diambil',
                'data' => WalletEscrowResource::collection($escrows),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * POST /api/wallet/withdraw
     * Withdraw balance for seller
     */
    public function withdraw(WithdrawRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $validated = $request->validated();

            $withdrawal = $this->walletService->processWithdrawal(
                $user,
                $validated['amount'],
                $validated['bank_name'],
                $validated['account_name'],
                $validated['account_number']
            );

            $wallet = $this->walletService->getOrCreateWalletAccount($user);

            // Format ID for response
            $formattedId = 'wd-' . $withdrawal->created_at->format('Ymd') . '-' . str_pad($withdrawal->id, 4, '0', STR_PAD_LEFT);

            return response()->json([
                'success' => true,
                'message' => 'Withdraw simulasi berhasil',
                'data' => [
                    'receipt' => [
                        'id' => $formattedId,
                        'reference_number' => $withdrawal->reference_number,
                        'amount' => $withdrawal->amount,
                        'bank_name' => $withdrawal->bank_name,
                        'account_name' => $withdrawal->account_name,
                        'account_number' => $withdrawal->account_number,
                        'created_at' => $withdrawal->created_at,
                    ],
                    'available_balance' => $wallet->available_balance,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 400);
        }
    }

    /**
     * GET /api/wallet/withdrawals
     * Get withdrawal history for seller
     */
    public function getWithdrawals(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $withdrawals = WalletWithdrawal::where('seller_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Riwayat withdraw berhasil diambil',
                'data' => WalletWithdrawalResource::collection($withdrawals),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
