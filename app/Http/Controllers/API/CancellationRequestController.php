<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateCancellationRequestRequest;
use App\Http\Requests\ApproveCancellationRequestRequest;
use App\Http\Requests\RejectCancellationRequestRequest;
use App\Models\CancellationRequest;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderTransactionActivity;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CancellationRequestController extends Controller
{
    private WalletService $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * 1️⃣ POST /api/orders/{id}/cancellation-request
     * Create cancellation request (by buyer)
     */
    public function store($id, CreateCancellationRequestRequest $request): JsonResponse
    {
        try {
            // Find order
            $order = Order::findOrFail($id);

            // Validate: order belongs to buyer
            if ($order->buyer_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak berhak mengajukan pembatalan untuk order ini',
                ], 403);
            }

            // Validate: order status harus dalam status yang bisa dibatalkan
            $cancellableStatuses = ['pending_payment', 'paid', 'processing'];
            if (!in_array($order->status, $cancellableStatuses)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order dengan status ini tidak bisa dibatalkan',
                ], 400);
            }

            // Validate: tidak boleh ada cancellation request sebelumnya untuk order ini
            $existingRequest = CancellationRequest::where('order_id', $id)->first();
            if ($existingRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sudah ada permohonan pembatalan untuk order ini',
                ], 409);
            }

            // Create cancellation request
            $cancellationRequest = CancellationRequest::create([
                'order_id' => $id,
                'buyer_id' => $request->user()->id,
                'seller_id' => $order->seller_id,
                'reason' => $request->reason,
                'details' => $request->details,
                'status' => 'pending',
            ]);

            // Send notification to seller
            Notification::create([
                'user_id' => $order->seller_id,
                'order_id' => $id,
                'title' => 'Permintaan Pembatalan Order',
                'message' => 'Buyer mengajukan permintaan pembatalan untuk order #' . $id,
                'type' => 'cancellation_request',
                'action_url' => '/seller/cancellation-requests',
                'action_label' => 'Lihat Permohonan',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Permohonan pembatalan berhasil dikirim',
                'data' => [
                    'id' => $cancellationRequest->id,
                    'order_id' => $cancellationRequest->order_id,
                    'buyer_id' => $cancellationRequest->buyer_id,
                    'seller_id' => $cancellationRequest->seller_id,
                    'reason' => $cancellationRequest->reason,
                    'details' => $cancellationRequest->details,
                    'status' => $cancellationRequest->status,
                    'created_at' => $cancellationRequest->created_at,
                ],
            ], 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Order tidak ditemukan',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan',
                'errors' => ['error' => $e->getMessage()],
            ], 500);
        }
    }

    /**
     * 2️⃣ GET /api/orders/{id}/cancellation-request
     * Get cancellation request status (by buyer or seller)
     */
    public function show($id, Request $request): JsonResponse
    {
        try {
            // Find order first
            $order = Order::findOrFail($id);

            // Validate: buyer or seller dapat akses
            $userId = $request->user()->id;
            if ($order->buyer_id !== $userId && $order->seller_id !== $userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak berhak melihat data pembatalan untuk order ini',
                ], 403);
            }

            // Find cancellation request
            $cancellationRequest = CancellationRequest::where('order_id', $id)->first();

            if (!$cancellationRequest) {
                return response()->json([
                    'success' => true,
                    'data' => null,
                ], 200);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $cancellationRequest->id,
                    'order_id' => $cancellationRequest->order_id,
                    'buyer_id' => $cancellationRequest->buyer_id,
                    'seller_id' => $cancellationRequest->seller_id,
                    'reason' => $cancellationRequest->reason,
                    'details' => $cancellationRequest->details,
                    'status' => $cancellationRequest->status,
                    'seller_notes' => $cancellationRequest->seller_notes,
                    'rejection_reason' => $cancellationRequest->rejection_reason,
                    'created_at' => $cancellationRequest->created_at,
                    'updated_at' => $cancellationRequest->updated_at,
                ],
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Order tidak ditemukan',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan',
                'errors' => ['error' => $e->getMessage()],
            ], 500);
        }
    }

    /**
     * 3️⃣ PUT /api/orders/{id}/cancellation-request/approve
     * Approve cancellation request (by seller) - release escrow to buyer
     */
    public function approve($id, ApproveCancellationRequestRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($id, $request) {
            try {
                // Find order
                $order = Order::findOrFail($id);

                // Validate: seller owns this order
                if ($order->seller_id !== $request->user()->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Anda tidak berhak menyetujui pembatalan order ini',
                    ], 403);
                }

                // Find cancellation request
                $cancellationRequest = CancellationRequest::where('order_id', $id)
                    ->where('status', 'pending')
                    ->first();

                if (!$cancellationRequest) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Permohonan pembatalan tidak ditemukan atau sudah diproses',
                    ], 400);
                }

                // Update cancellation request status
                $cancellationRequest->update([
                    'status' => 'approved',
                    'seller_notes' => $request->seller_notes,
                ]);

                // Update order status
                $order->update(['status' => 'cancelled']);

                // Release escrow back to buyer
                $escrow = $order->walletEscrow;
                if ($escrow && $escrow->status === 'held') {
                    $this->walletService->refundFundsToBuyer($escrow);
                    $refundedAmount = $escrow->amount;
                } else {
                    $refundedAmount = 0;
                }

                // Create activity log
                OrderTransactionActivity::create([
                    'order_id' => $id,
                    'actor_id' => $request->user()->id,
                    'actor_role' => 'seller',
                    'action' => 'cancellation_approved',
                    'description' => 'Seller menyetujui permohonan pembatalan',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'metadata' => [
                        'cancellation_reason' => $cancellationRequest->reason,
                        'refunded_amount' => $refundedAmount,
                    ],
                    'created_at' => now(),
                ]);

                // Send notification to buyer: approval
                Notification::create([
                    'user_id' => $order->buyer_id,
                    'order_id' => $id,
                    'title' => 'Pesanan Dibatalkan',
                    'message' => 'Permohonan pembatalan pesanan Anda telah disetujui. Dana sebesar ' .
                        'Rp' . number_format($refundedAmount, 0, ',', '.') . ' telah dikembalikan ke dompet Anda',
                    'type' => 'cancellation_approved',
                    'action_url' => '/orders/' . $id,
                    'action_label' => 'Lihat Pesanan',
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Permintaan pembatalan disetujui',
                    'data' => [
                        'id' => $order->id,
                        'status' => $order->status,
                        'refunded_amount' => $refundedAmount,
                        'refunded_to_wallet' => true,
                    ],
                ], 200);
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order tidak ditemukan',
                ], 404);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terjadi kesalahan',
                    'errors' => ['error' => $e->getMessage()],
                ], 500);
            }
        });
    }

    /**
     * 4️⃣ PUT /api/orders/{id}/cancellation-request/reject
     * Reject cancellation request (by seller)
     */
    public function reject($id, RejectCancellationRequestRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($id, $request) {
            try {
                // Find order
                $order = Order::findOrFail($id);

                // Validate: seller owns this order
                if ($order->seller_id !== $request->user()->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Anda tidak berhak menolak pembatalan order ini',
                    ], 403);
                }

                // Find cancellation request
                $cancellationRequest = CancellationRequest::where('order_id', $id)
                    ->where('status', 'pending')
                    ->first();

                if (!$cancellationRequest) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Permohonan pembatalan tidak ditemukan atau sudah diproses',
                    ], 400);
                }

                // Update cancellation request status (order tetap normal)
                $cancellationRequest->update([
                    'status' => 'rejected',
                    'rejection_reason' => $request->reason,
                ]);

                // Create activity log
                OrderTransactionActivity::create([
                    'order_id' => $id,
                    'actor_id' => $request->user()->id,
                    'actor_role' => 'seller',
                    'action' => 'cancellation_rejected',
                    'description' => 'Seller menolak permohonan pembatalan',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'metadata' => [
                        'rejection_reason' => $request->reason,
                    ],
                    'created_at' => now(),
                ]);

                // Send notification to buyer: rejection
                Notification::create([
                    'user_id' => $order->buyer_id,
                    'order_id' => $id,
                    'title' => 'Permintaan Pembatalan Ditolak',
                    'message' => 'Permintaan pembatalan pesanan Anda telah ditolak. Alasan: ' . $request->reason,
                    'type' => 'cancellation_rejected',
                    'action_url' => '/orders/' . $id,
                    'action_label' => 'Lihat Pesanan',
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Permintaan pembatalan ditolak',
                    'data' => [
                        'id' => $cancellationRequest->id,
                        'status' => $cancellationRequest->status,
                        'rejection_reason' => $cancellationRequest->rejection_reason,
                    ],
                ], 200);
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order tidak ditemukan',
                ], 404);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terjadi kesalahan',
                    'errors' => ['error' => $e->getMessage()],
                ], 500);
            }
        });
    }

    /**
     * 5️⃣ GET /api/seller/cancellation-requests
     * Get all cancellation requests for seller (paginated)
     */
    public function sellerRequests(Request $request): JsonResponse
    {
        try {
            $page = $request->query('page', 1);
            $status = $request->query('status'); // optional: pending, approved, rejected
            $perPage = 50;

            $query = CancellationRequest::where('seller_id', $request->user()->id)
                ->with(['order', 'buyer', 'seller'])
                ->byStatus($status)
                ->orderBy('created_at', 'desc');

            $cancellationRequests = $query->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'data' => $cancellationRequests->map(function ($cr) {
                    return [
                        'id' => $cr->id,
                        'order_id' => $cr->order_id,
                        'buyer_id' => $cr->buyer_id,
                        'seller_id' => $cr->seller_id,
                        'buyer_name' => $cr->buyer?->name,
                        'order_total' => $cr->order?->total_price,
                        'reason' => $cr->reason,
                        'details' => $cr->details,
                        'status' => $cr->status,
                        'seller_notes' => $cr->seller_notes,
                        'rejection_reason' => $cr->rejection_reason,
                        'created_at' => $cr->created_at,
                        'updated_at' => $cr->updated_at,
                    ];
                })->toArray(),
                'pagination' => [
                    'current_page' => $cancellationRequests->currentPage(),
                    'total' => $cancellationRequests->total(),
                    'per_page' => $cancellationRequests->perPage(),
                    'last_page' => $cancellationRequests->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan',
                'errors' => ['error' => $e->getMessage()],
            ], 500);
        }
    }
}
