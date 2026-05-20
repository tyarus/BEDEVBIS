<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\OrderTransactionChatController;
use App\Http\Controllers\API\PaymentController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\WalletController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

// Auth Routes - with rate limiting
Route::middleware('throttle:5,1')->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
});

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Products - Public Read (no auth required for index/show)
    Route::post('/products', [ProductController::class, 'store']); // Seller only
    Route::put('/products/{id}', [ProductController::class, 'update']); // Seller only
    Route::delete('/products/{id}', [ProductController::class, 'destroy']); // Seller only
    Route::get('/seller/products', [ProductController::class, 'sellerProducts']); // Seller only

    // Orders
    Route::post('/orders', [OrderController::class, 'store']); // Buyer only
    Route::get('/orders', [OrderController::class, 'index']); // Buyer orders
    Route::get('/orders/{id}', [OrderController::class, 'show']); // Buyer/Seller view
    Route::get('/seller/orders', [OrderController::class, 'sellerOrders']); // Seller only
    Route::put('/seller/orders/{id}/ship', [OrderController::class, 'ship']); // Seller only
    Route::put('/orders/{id}/confirm', [OrderController::class, 'confirm']); // Buyer only
    Route::put('/orders/{id}/cancel', [OrderController::class, 'cancel']); // Buyer only

    // Payments
    Route::post('/orders/{id}/pay', [PaymentController::class, 'pay']); // Buyer only

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::put('/notifications/{id}/mark-as-read', [NotificationController::class, 'markAsRead']);
    Route::put('/notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);

    // Transaction Chat & Fraud Tracking
    Route::get('/orders/{id}/transaction-chat', [OrderTransactionChatController::class, 'show']);
    Route::post('/orders/{id}/transaction-chat/messages', [OrderTransactionChatController::class, 'storeMessage']);
    Route::put('/orders/{id}/transaction-chat/checklist', [OrderTransactionChatController::class, 'updateChecklist']);
    Route::patch('/orders/{id}/transaction-chat/checklist', [OrderTransactionChatController::class, 'updateChecklist']);
    Route::put('/orders/{id}/transaction-chat/status', [OrderTransactionChatController::class, 'updateStatus']);
    Route::patch('/orders/{id}/transaction-chat/status', [OrderTransactionChatController::class, 'updateStatus']);
    Route::post('/orders/{id}/transaction-chat/completion-code', [OrderTransactionChatController::class, 'generateCompletionCode']);
    Route::post('/orders/{id}/transaction-chat/verify-completion-code', [OrderTransactionChatController::class, 'verifyCompletionCode']);

    // Wallet System
    Route::get('/wallet/me', [WalletController::class, 'getWallet']);
    Route::post('/wallet/topup', [WalletController::class, 'topup']);
    Route::get('/wallet/ledger', [WalletController::class, 'getLedger']);
    Route::get('/wallet/escrows', [WalletController::class, 'getEscrows']);
    Route::post('/wallet/withdraw', [WalletController::class, 'withdraw']);
    Route::get('/wallet/withdrawals', [WalletController::class, 'getWithdrawals']);
});

// Public Product Routes
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);
