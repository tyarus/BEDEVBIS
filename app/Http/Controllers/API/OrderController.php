<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrderRequest;
use App\Http\Requests\ShipOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\EscrowLog;
use App\Models\Order;
use App\Models\Product;
use App\Models\WalletEscrow;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    private WalletService $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }
    public function store(CreateOrderRequest $request): JsonResponse
    {
        try {
            $product = Product::active()->findOrFail($request->product_id);

            // Check stock
            if ($product->stock < $request->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stok produk tidak cukup',
                    'errors' => ['stock' => 'Stok tidak mencukupi untuk quantity yang diminta'],
                ], 400);
            }

            // Create order
            $order = Order::create([
                'buyer_id' => $request->user()->id,
                'product_id' => $request->product_id,
                'seller_id' => $product->seller_id,
                'quantity' => $request->quantity,
                'total_price' => $product->price * $request->quantity,
                'status' => 'pending_payment',
            ]);

            // Create escrow log
            EscrowLog::create([
                'order_id' => $order->id,
                'actor_id' => $request->user()->id,
                'action' => 'order_created',
                'note' => 'Order dibuat oleh buyer',
            ]);

            // Load relationships
            $order->load(['buyer', 'seller', 'product']);

            return response()->json([
                'success' => true,
                'message' => 'Order berhasil dibuat',
                'data' => new OrderResource($order),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan',
                'errors' => ['error' => $e->getMessage()],
            ], 500);
        }
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $orders = Order::where('buyer_id', $request->user()->id)
                ->with(['buyer', 'seller', 'product', 'payments'])
                ->paginate(12);

            return response()->json([
                'success' => true,
                'message' => 'Daftar order berhasil diambil',
                'data' => OrderResource::collection($orders),
                'pagination' => [
                    'total' => $orders->total(),
                    'per_page' => $orders->perPage(),
                    'current_page' => $orders->currentPage(),
                    'last_page' => $orders->lastPage(),
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

    public function show($id, Request $request): JsonResponse
    {
        try {
            $order = Order::findOrFail($id);

            // Check if user is buyer or seller of the order
            if ($order->buyer_id !== $request->user()->id && $order->seller_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki izin melihat order ini',
                    'errors' => [],
                ], 403);
            }

            $order->load(['buyer', 'seller', 'product', 'payments']);

            return response()->json([
                'success' => true,
                'message' => 'Detail order berhasil diambil',
                'data' => new OrderResource($order),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Order tidak ditemukan',
                'errors' => [],
            ], 404);
        }
    }

    public function sellerOrders(Request $request): JsonResponse
    {
        try {
            $orders = Order::where('seller_id', $request->user()->id)
                ->with(['buyer', 'seller', 'product', 'payments'])
                ->paginate(12);

            return response()->json([
                'success' => true,
                'message' => 'Daftar order seller berhasil diambil',
                'data' => OrderResource::collection($orders),
                'pagination' => [
                    'total' => $orders->total(),
                    'per_page' => $orders->perPage(),
                    'current_page' => $orders->currentPage(),
                    'last_page' => $orders->lastPage(),
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

    public function ship(ShipOrderRequest $request, $id): JsonResponse
    {
        try {
            $order = Order::findOrFail($id);

            // Check if user is the seller
            if ($order->seller_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki izin mengupdate order ini',
                    'errors' => [],
                ], 403);
            }

            // Check if order is paid
            if ($order->status !== 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'Order harus dalam status paid',
                    'errors' => [],
                ], 400);
            }

            // Update order
            $order->update([
                'status' => 'shipped',
                'tracking_number' => $request->tracking_number,
            ]);

            // Create escrow log
            EscrowLog::create([
                'order_id' => $order->id,
                'actor_id' => $request->user()->id,
                'action' => 'order_shipped',
                'note' => 'Tracking: ' . $request->tracking_number,
            ]);

            $order->load(['buyer', 'seller', 'product']);

            return response()->json([
                'success' => true,
                'message' => 'Order berhasil dikirim',
                'data' => new OrderResource($order),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Order tidak ditemukan',
                'errors' => [],
            ], 404);
        }
    }

    public function confirm($id, Request $request): JsonResponse
    {
        try {
            $order = Order::findOrFail($id);

            // Check if user is the buyer
            if ($order->buyer_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki izin mengkonfirmasi order ini',
                    'errors' => [],
                ], 403);
            }

            // Check if order is in a confirmable status
            if (!in_array($order->status, ['shipped', 'delivered'], true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order harus dalam status shipped atau delivered',
                    'errors' => [],
                ], 422);
            }

            // Try to release escrow if it exists (for ewallet payments)
            $escrow = WalletEscrow::where('order_id', $order->id)->first();
            if ($escrow && $escrow->status === 'held') {
                try {
                    $this->walletService->releaseFundsToSeller($escrow);
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Gagal melepaskan dana escrow: ' . $e->getMessage(),
                        'errors' => [],
                    ], 400);
                }
            }
            // If no escrow exists, it's a non-wallet payment (bank_transfer/virtual_account)

            // Update order
            $order->update(['status' => 'completed']);

            // Create escrow log
            EscrowLog::create([
                'order_id' => $order->id,
                'actor_id' => $request->user()->id,
                'action' => 'order_confirmed',
                'amount' => $order->total_price,
                'note' => 'Order dikonfirmasi dan selesai',
            ]);

            $order->load(['buyer', 'seller', 'product']);

            return response()->json([
                'success' => true,
                'message' => 'Order berhasil dikonfirmasi',
                'data' => new OrderResource($order),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Order tidak ditemukan',
                'errors' => [],
            ], 404);
        }
    }

    public function cancel($id, Request $request): JsonResponse
    {
        try {
            $order = Order::findOrFail($id);

            // Check if user is the buyer
            if ($order->buyer_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki izin membatalkan order ini',
                    'errors' => [],
                ], 403);
            }

            // Check if order is pending payment (cancel only for unpaid orders)
            // But if already paid, need to refund from escrow
            if (!in_array($order->status, ['pending_payment', 'paid', 'shipped'], true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order dengan status ' . $order->status . ' tidak bisa dibatalkan',
                    'errors' => [],
                ], 400);
            }

            // If order is paid or shipped, try to refund escrow
            if (in_array($order->status, ['paid', 'shipped'])) {
                try {
                    $escrow = WalletEscrow::where('order_id', $order->id)->firstOrFail();
                    if ($escrow->status === 'held') {
                        $this->walletService->refundFundsToBuyer($escrow);
                    }
                } catch (\Exception $e) {
                    // If no escrow or refund fails, log but continue
                    \Log::warning("Failed to refund escrow for order {$id}: " . $e->getMessage());
                }
            }

            // Update order
            $order->update(['status' => 'cancelled']);

            // Create escrow log
            EscrowLog::create([
                'order_id' => $order->id,
                'actor_id' => $request->user()->id,
                'action' => 'order_cancelled',
                'note' => 'Order dibatalkan oleh buyer',
            ]);

            $order->load(['buyer', 'seller', 'product']);

            return response()->json([
                'success' => true,
                'message' => 'Order berhasil dibatalkan',
                'data' => new OrderResource($order),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Order tidak ditemukan',
                'errors' => [],
            ], 404);
        }
    }
}
