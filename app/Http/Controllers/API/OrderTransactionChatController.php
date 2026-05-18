<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateTransactionMessageRequest;
use App\Http\Requests\UpdateTransactionChecklistRequest;
use App\Http\Requests\UpdateTransactionStatusRequest;
use App\Http\Requests\VerifyCompletionCodeRequest;
use App\Http\Resources\OrderTransactionChatResource;
use App\Models\Order;
use App\Models\OrderTransactionChat;
use App\Models\OrderTransactionMessage;
use App\Models\OrderTransactionChecklist;
use App\Models\OrderTransactionActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class OrderTransactionChatController extends Controller
{
    /**
     * Get transaction chat with messages and activities
     */
    public function show(Request $request, $orderId): JsonResponse
    {
        try {
            $order = Order::findOrFail($orderId);

            // Check authorization
            if (!$this->isAuthorized($request, $order)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses ke order ini',
                ], 403);
            }

            $chat = OrderTransactionChat::where('order_id', $orderId)
                ->with(['checklist', 'messages', 'activities'])
                ->first();

            if (!$chat) {
                // Create new transaction chat if not exists
                $chat = OrderTransactionChat::create([
                    'order_id' => $orderId,
                    'status' => 'chat_open',
                ]);

                // Create checklist
                OrderTransactionChecklist::create([
                    'order_id' => $orderId,
                ]);

                // Log activity
                $this->logActivity($orderId, $request->user()->id, $request->user()->role, 'transaction_chat_opened', 'Transaction chat dibuka', $request);
            }

            return response()->json([
                'success' => true,
                'message' => 'Data transaksi chat berhasil diambil',
                'data' => new OrderTransactionChatResource($chat),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan',
                'errors' => ['error' => $e->getMessage()],
            ], 500);
        }
    }

    /**
     * Store a new transaction message
     */
    public function storeMessage(CreateTransactionMessageRequest $request, $orderId): JsonResponse
    {
        try {
            $order = Order::findOrFail($orderId);

            // Check authorization
            if (!$this->isAuthorized($request, $order)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses untuk mengirim pesan',
                ], 403);
            }

            // Create message
            $message = OrderTransactionMessage::create([
                'order_id' => $orderId,
                'sender_id' => $request->user()->id,
                'sender_role' => $request->user()->role,
                'message' => $request->message,
                'message_type' => $request->message_type,
                'metadata' => $request->only(['metadata']),
            ]);

            // Log activity
            $this->logActivity($orderId, $request->user()->id, $request->user()->role, 'message_sent', "Pesan dikirim: {$request->message_type}", $request);

            return response()->json([
                'success' => true,
                'message' => 'Pesan berhasil dikirim',
                'data' => [
                    'id' => $message->id,
                    'message' => $message->message,
                    'message_type' => $message->message_type,
                    'sender_id' => $message->sender_id,
                    'sender_role' => $message->sender_role,
                    'created_at' => $message->created_at,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan',
                'errors' => ['error' => $e->getMessage()],
            ], 500);
        }
    }

    /**
     * Update transaction checklist
     */
    public function updateChecklist(UpdateTransactionChecklistRequest $request, $orderId): JsonResponse
    {
        try {
            $order = Order::findOrFail($orderId);

            // Check authorization
            if (!$this->isAuthorized($request, $order)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses untuk mengubah checklist',
                ], 403);
            }

            $checklist = OrderTransactionChecklist::where('order_id', $orderId)->first();
            if (!$checklist) {
                $checklist = OrderTransactionChecklist::create([
                    'order_id' => $orderId,
                ]);
            }

            $data = $request->validated();
            $data['updated_by'] = $request->user()->id;
            $checklist->update($data);

            // Log activity
            $this->logActivity($orderId, $request->user()->id, $request->user()->role, 'checklist_updated', 'Checklist diperbarui', $request);

            return response()->json([
                'success' => true,
                'message' => 'Checklist berhasil diperbarui',
                'data' => [
                    'account_match' => $checklist->account_match,
                    'account_secured' => $checklist->account_secured,
                    'seller_device_removed' => $checklist->seller_device_removed,
                    'completion_code_verified' => $checklist->completion_code_verified,
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

    /**
     * Update transaction status
     */
    public function updateStatus(UpdateTransactionStatusRequest $request, $orderId): JsonResponse
    {
        try {
            $order = Order::findOrFail($orderId);

            // Check authorization
            if (!$this->isAuthorized($request, $order)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses untuk mengubah status',
                ], 403);
            }

            $chat = OrderTransactionChat::where('order_id', $orderId)->first();
            if (!$chat) {
                $chat = OrderTransactionChat::create([
                    'order_id' => $orderId,
                ]);
            }

            $oldStatus = $chat->status;
            $chat->update(['status' => $request->status]);

            // Log activity
            $this->logActivity($orderId, $request->user()->id, $request->user()->role, 'status_changed', "Status diubah dari {$oldStatus} ke {$request->status}", $request);

            return response()->json([
                'success' => true,
                'message' => 'Status berhasil diperbarui',
                'data' => [
                    'status' => $chat->status,
                    'updated_at' => $chat->updated_at,
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

    /**
     * Generate completion code
     */
    public function generateCompletionCode(Request $request, $orderId): JsonResponse
    {
        try {
            $order = Order::findOrFail($orderId);

            // Check authorization - only seller can generate
            if ($request->user()->id !== $order->seller_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya penjual yang dapat membuat kode penyelesaian',
                ], 403);
            }

            $chat = OrderTransactionChat::where('order_id', $orderId)->first();
            if (!$chat) {
                $chat = OrderTransactionChat::create([
                    'order_id' => $orderId,
                ]);
            }

            // Generate random code in format XXXX-XXXX (8 chars total)
            $part1 = strtoupper(substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 4));
            $part2 = strtoupper(substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 4));
            $code = $part1 . '-' . $part2;

            // Hash for storage (hash the code without dash for consistency)
            $codeForHash = str_replace('-', '', $code);
            $codeHash = Hash::make($codeForHash);
            $expiresAt = Carbon::now()->addHours(24);

            // Store raw code temporarily for display (will be cleared after verification)
            // Store in metadata as encrypted string or separate field
            $chat->update([
                'completion_code_hash' => $codeHash,
                'completion_code_expires_at' => $expiresAt,
                'completion_code_verified_at' => null, // Reset verification
            ]);

            // Store the actual code in cache for 5 minutes (for FE to retrieve if needed)
            \Illuminate\Support\Facades\Cache::put(
                "completion_code_{$orderId}",
                $code,
                now()->addMinutes(5)
            );

            // Log activity
            $this->logActivity($orderId, $request->user()->id, $request->user()->role, 'completion_code_generated', 'Kode penyelesaian dibuat', $request);

            return response()->json([
                'success' => true,
                'message' => 'Kode penyelesaian berhasil dibuat',
                'data' => [
                    'completion_code' => $code,
                    'expires_at' => $expiresAt,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan',
                'errors' => ['error' => $e->getMessage()],
            ], 500);
        }
    }

    /**
     * Verify completion code
     */
    public function verifyCompletionCode(VerifyCompletionCodeRequest $request, $orderId): JsonResponse
    {
        try {
            $order = Order::findOrFail($orderId);

            // Check authorization - only buyer can verify
            if ($request->user()->id !== $order->buyer_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya pembeli yang dapat memverifikasi kode',
                ], 403);
            }

            $chat = OrderTransactionChat::where('order_id', $orderId)->first();
            if (!$chat) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada kode penyelesaian untuk transaksi ini',
                ], 404);
            }

            // Check if code is expired
            if ($chat->completion_code_expires_at && Carbon::now()->isAfter($chat->completion_code_expires_at)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kode penyelesaian sudah kadaluarsa',
                ], 400);
            }

            // Strip dash from input code and verify
            $userCode = str_replace('-', '', $request->code);

            if (!$chat->completion_code_hash || !Hash::check($userCode, $chat->completion_code_hash)) {
                // Log failed attempt
                $this->logActivity($orderId, $request->user()->id, $request->user()->role, 'completion_code_verify_failed', 'Gagal memverifikasi kode', $request);

                return response()->json([
                    'success' => false,
                    'message' => 'Kode penyelesaian tidak valid',
                ], 400);
            }

            // Update verification
            $verifiedAt = Carbon::now();
            $chat->update(['completion_code_verified_at' => $verifiedAt]);

            // Update checklist
            $checklist = OrderTransactionChecklist::where('order_id', $orderId)->first();
            if ($checklist) {
                $checklist->update(['completion_code_verified' => true]);
            }

            // Log successful verification
            $this->logActivity($orderId, $request->user()->id, $request->user()->role, 'completion_code_verified', 'Kode penyelesaian berhasil diverifikasi', $request);

            return response()->json([
                'success' => true,
                'message' => 'Kode valid',
                'data' => [
                    'verified' => true,
                    'status' => 'completed',
                    'verified_at' => $verifiedAt,
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

    /**
     * Helper: Check if user is authorized for this order
     */
    private function isAuthorized($request, $order): bool
    {
        $userId = $request->user()->id;
        return $userId === $order->buyer_id || $userId === $order->seller_id;
    }

    /**
     * Helper: Log activity with IP and User Agent
     */
    private function logActivity($orderId, $actorId, $actorRole, $action, $description, $request): void
    {
        OrderTransactionActivity::create([
            'order_id' => $orderId,
            'actor_id' => $actorId,
            'actor_role' => $actorRole,
            'action' => $action,
            'description' => $description,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
