# Wallet System + Escrow Implementation (Revised)

## Overview

Implementasi backend wallet system dengan escrow dan payment method logic. Wallet operations hanya terjadi untuk payment method `ewallet`, sementara `bank_transfer` dan `virtual_account` bypass wallet system.

## 📁 Files Created

### Database Migrations

- [2026_05_20_000001_create_wallet_accounts_table.php](database/migrations/2026_05_20_000001_create_wallet_accounts_table.php)
- [2026_05_20_000002_create_wallet_escrows_table.php](database/migrations/2026_05_20_000002_create_wallet_escrows_table.php)
- [2026_05_20_000003_create_wallet_ledger_entries_table.php](database/migrations/2026_05_20_000003_create_wallet_ledger_entries_table.php)
- [2026_05_20_000004_create_wallet_withdrawals_table.php](database/migrations/2026_05_20_000004_create_wallet_withdrawals_table.php)

### Eloquent Models

- [app/Models/WalletAccount.php](app/Models/WalletAccount.php)
- [app/Models/WalletEscrow.php](app/Models/WalletEscrow.php)
- [app/Models/WalletLedgerEntry.php](app/Models/WalletLedgerEntry.php)
- [app/Models/WalletWithdrawal.php](app/Models/WalletWithdrawal.php)

### Service Layer

- [app/Services/WalletService.php](app/Services/WalletService.php) - Business logic untuk wallet operations

### Controllers

- [app/Http/Controllers/API/WalletController.php](app/Http/Controllers/API/WalletController.php) - Wallet endpoints

### Request Validators

- [app/Http/Requests/TopupRequest.php](app/Http/Requests/TopupRequest.php)
- [app/Http/Requests/WithdrawRequest.php](app/Http/Requests/WithdrawRequest.php)

### API Resources

- [app/Http/Resources/WalletResource.php](app/Http/Resources/WalletResource.php)
- [app/Http/Resources/WalletLedgerEntryResource.php](app/Http/Resources/WalletLedgerEntryResource.php)
- [app/Http/Resources/WalletEscrowResource.php](app/Http/Resources/WalletEscrowResource.php)
- [app/Http/Resources/WalletWithdrawalResource.php](app/Http/Resources/WalletWithdrawalResource.php)

### Modified Files

- [routes/api.php](routes/api.php) - Added wallet routes
- [app/Http/Controllers/API/PaymentController.php](app/Http/Controllers/API/PaymentController.php) - Added payment_method logic for ewallet
- [app/Http/Controllers/API/OrderController.php](app/Http/Controllers/API/OrderController.php) - Enhanced confirm & cancel with escrow checks
- [app/Models/User.php](app/Models/User.php) - Added wallet relationships
- [app/Models/Order.php](app/Models/Order.php) - Added walletEscrow relationship

## 🔄 Alur Transaksi

### 1. Buyer Top Up (Simulasi)

```
POST /api/wallet/topup
{
  "amount": 100000
}

// WalletService.processTopup():
// - Increment buyer's available_balance
// - Increment total_topup
// - Create ledger entry (type: topup, direction: credit)
```

### 2. Buyer Membayar Order

```
POST /api/orders/{id}/pay
{
  "payment_method": "ewallet" | "bank_transfer" | "virtual_account"
}

// PaymentController.pay():
// Jika payment_method = "ewallet" (Crowalet):
//   - WalletService.holdFundsForOrder():
//     - Validasi buyer punya cukup saldo
//     - Deduct buyer's available_balance
//     - Create WalletEscrow dengan status 'held'
//     - Create ledger entry (type: order_hold, direction: debit)
// Jika payment_method = "bank_transfer" atau "virtual_account":
//   - SKIP wallet operation (no escrow created)
//   - Proses pembayaran tetap lanjut sebagai simulasi non-wallet
// - Update order status ke 'paid'
```

### 3. Order Selesai (Confirm)

```
PUT /api/orders/{id}/confirm

// OrderController.confirm():
// - Jika escrow exists dengan status 'held' (dari ewallet payment):
//   - WalletService.releaseFundsToSeller():
//     - Validasi escrow status = 'held'
//     - Add to seller's available_balance
//     - Increment seller's total_sales
//     - Update escrow status ke 'released' & set released_at
//     - Create ledger entry (type: order_release, direction: credit)
// - Jika tidak ada escrow (dari bank_transfer/virtual_account):
//   - Skip wallet operation
// - Update order status ke 'completed'
```

### 4. Order Dibatalkan (Cancel)

```Check order status: dapat dibatalkan jika pending_payment, paid, atau shipped
// - Jika status adalah paid atau shipped:
//   - Check if escrow exists dengan status 'held'
//   - Jika ada, WalletService.refundFundsToBuyer():
//     - Add back to buyer's available_balance
//     - Increment buyer's total_refund
//     - Update escrow status ke 'refunded' & set refunded_at
//     - Create ledger entry (type: order_refund, direction: credit)
// - Update order status ke 'cancelled'
// - Add back to buyer's available_balance
// - Increment buyer's total_refund
// - Update escrow status ke 'refunded' & set refunded_at
// - Create ledger entry (type: order_refund, direction: credit)
```

### 5. Seller Withdraw

```
POST /api/wallet/withdraw
{
  "amount": 150000,
  "bank_name": "BCA",
  "account_name": "Tyar Seller",
  "account_number": "1234567890"
}

// WalletService.processWithdrawal():
// - Validasi seller punya cukup saldo
// - Deduct seller's available_balance
// - Increment total_withdraw
// - Create WalletWithdrawal dengan reference_number
// - Create ledger entry (type: withdraw, direction: debit)
```

## 📊 Wallet Endpoints

### GET /api/wallet/me

Mendapatkan informasi wallet user saat ini

**Response:**

```json
{
    "success": true,
    "message": "Data wallet berhasil diambil",
    "data": {
        "available_balance": 1500000,
        "held_amount_as_buyer": 250000,
        "held_amount_as_seller": 100000,
        "total_topup": 2000000,
        "total_sales": 300000,
        "total_withdraw": 150000,
        "total_refund": 50000
    }
}
```

### POST /api/wallet/topup (Buyer Only)

Top up simulasi untuk buyer

**Request:**

```json
{
    "amount": 100000
}
```

**Response (200/201):**

```json
{
    "success": true,
    "message": "Top up simulasi berhasil",
    "data": {
        "available_balance": 1600000
    }
}
```

### GET /api/wallet/ledger

Mendapatkan riwayat transaksi wallet

**Response:**

```json
{
    "success": true,
    "message": "Riwayat wallet berhasil diambil",
    "data": [
        {
            "id": 101,
            "type": "topup",
            "direction": "credit",
            "amount": 100000,
            "balance_after": 1600000,
            "description": "Top up saldo simulasi",
            "order_id": null,
            "created_at": "2026-05-20T09:00:00.000000Z"
        }
    ]
}
```

### GET /api/wallet/escrows

Mendapatkan daftar escrow (user bisa melihat sebagai buyer atau seller)

**Response:**

```json
{
    "success": true,
    "message": "Data escrow berhasil diambil",
    "data": [
        {
            "order_id": 77,
            "buyer_id": 11,
            "seller_id": 22,
            "amount": 250000,
            "status": "held",
            "created_at": "2026-05-20T09:30:00.000000Z",
            "updated_at": "2026-05-20T09:30:00.000000Z",
            "released_at": null,
            "refunded_at": null
        }
    ]
}
```

### POST /api/wallet/withdraw (Seller Only)

Withdraw simulasi untuk seller

**Request:**

```json
{
    "amount": 150000,
    "bank_name": "BCA",
    "account_name": "Tyar Seller",
    "account_number": "1234567890"
}
```

**Response (Status 200/201):**

```json
{
    "success": true,
    "message": "Withdraw simulasi berhasil",
    "data": {
        "receipt": {
            "id": "wd-20260520-0001",
            "reference_number": "WD-20260520122530000022",
            "amount": 150000,
            "bank_name": "BCA",
            "account_name": "Tyar Seller",
            "account_number": "1234567890",
            "created_at": "2026-05-20T10:00:00.000000Z"
        },
        "available_balance": 450000
    }
}
```

### GET /api/wallet/withdrawals (Seller Only)

Mendapatkan riwayat withdraw

**Response:**

```json
{
    "success": true,
    "message": "Riwayat withdraw berhasil diambil",
    "data": [
        {
            "id": "wd-20260520-0001",
            "reference_number": "WD-20260520122530000022",
            "amount": 150000,
            "bank_name": "BCA",
            "account_name": "Tyar Seller",
            "account_number": "1234567890",
            "created_at": "2026-05-20T10:00:00.000000Z"
        }
    ]
}
```

## 🔐 Authorization & Validation

### TopupRequest

- Hanya buyer yang bisa top up
- Amount harus integer min 1

### WithdrawRequest

- Hanya seller yang bisa withdraw
- Amount harus integer min 1
- bank_name, account_name, account_number required

### PaymentMethod Validation

- Valid values: `ewallet`, `bank_transfer`, `virtual_account`
- Hanya `ewallet` yang menggunakan wallet system
- `bank_transfer` & `virtual_account` bypass wallet

### Error Codes

- `200/201`: Success
- `400`: General error / validation gagal
- `403`: Role tidak sesuai / no permission
- `404`: Resource tidak ditemukan
- `409`: Insufficient balance / invalid escrow status
- `422`: Validation error

## 💾 Database Transactions

Semua wallet operations menggunakan DB::transaction untuk memastikan konsistensi data:

```php
// Contoh: holdFundsForOrder (ewallet payment)
return DB::transaction(function () use ($buyer, $seller, $orderId, $amount) {
    // 1. Validasi buyer punya cukup saldo
    // 2. Deduct buyer's available_balance
    // 3. Create ledger entry
    // 4. Create escrow
    // Jika ada error, semua rollback
});
```

## 📝 Ledger Entry Types

| Type            | Direction | When                              | User   |
| --------------- | --------- | --------------------------------- | ------ |
| `topup`         | Credit    | Buyer top up saldo simulasi       | Buyer  |
| `order_hold`    | Debit     | Buyer bayar dengan ewallet        | Buyer  |
| `order_release` | Credit    | Order completed & escrow released | Seller |
| `order_refund`  | Credit    | Order cancelled & escrow refunded | Buyer  |
| `withdraw`      | Debit     | Seller withdraw ke rekening bank  | Seller |

## 🎯 Payment Method Flow

### ewallet (Crowalet)

- **Payment**: Create escrow, hold funds
- **Confirm**: Release escrow to seller
- **Cancel**: Refund from escrow to buyer
- **Escrow**: CREATED
- **Ledger**: 3 entries (hold → release/refund)

### bank_transfer / virtual_account

- **Payment**: Direct (no escrow)
- **Confirm**: Simply mark completed
- **Cancel**: No refund needed
- **Escrow**: NOT CREATED
- **Ledger**: No entries

## ✅ Testing Checklist

### Top Up Flow

- [ ] Buyer bisa top up dan saldo bertambah
- [ ] Ledger entry tercatat dengan type `topup`
- [ ] Total topup updated

### Payment with ewallet

- [ ] Buyer pembayaran mengurangi available_balance
- [ ] Escrow created dengan status `held`
- [ ] Ledger entry tercatat dengan type `order_hold`
- [ ] Order status updated ke `paid`

### Payment with bank_transfer/virtual_account

- [ ] Buyer payment TIDAK mengurangi saldo
- [ ] Escrow NOT created
- [ ] Order status updated ke `paid`
- [ ] No ledger entry created

### Order Confirmation

- [ ] Escrow released ke seller (jika ewallet)
- [ ] Seller available_balance bertambah (jika ewallet)
- [ ] Seller total_sales incremented (jika ewallet)
- [ ] Order status updated ke `completed`

### Order Cancellation

- [ ] Cancel pending_payment: no wallet operation
- [ ] Cancel paid order (ewallet): escrow refunded
- [ ] Cancel paid order (bank): no wallet operation
- [ ] Buyer balance increased (jika refund)

### Withdrawal

- [ ] Seller bisa withdraw dengan cukup saldo
- [ ] Available balance berkurang
- [ ] Total withdraw incremented
- [ ] Withdrawal ID formatted: wd-YYYYMMDD-XXXX

## 📌 Key Differences from Initial Implementation

1. **Payment Method Logic**:
    - Only `ewallet` uses wallet system
    - `bank_transfer` & `virtual_account` skip wallet operations
    - This allows flexibility for multiple payment methods

2. **Order Cancellation**:
    - Can now cancel `paid` and `shipped` orders
    - Refund only happens if escrow exists with `held` status
    - Gracefully handles non-wallet payments

3. **Order Confirmation**:
    - Escrow release is optional (checks if exists)
    - Works for both wallet and non-wallet payments

4. **Withdrawal ID Format**:
    - Display format: `wd-YYYYMMDD-XXXX`
    - Numeric ID in database preserved for performance

## 📌 Notes

- Wallet account di-auto-create pertama kali digunakan (lazy loading)
- Reference number: `WD-{timestamp_HiS}-{padded_user_id}`
- Withdrawal ID display: `wd-{YYYYMMDD}-{padded_numeric_id}`
- Semua balance operations dalam integer (Rupiah tanpa decimal)
- Held amount calculated dari sum escrow with status 'held'
- Ledger immutable (hanya insert, tidak ada update/delete)
- Escrow only created untuk `ewallet` payment method
- Non-wallet payments bypass semua wallet logic

## 🚀 Setup & Migration

1. **Run migrations:**

```bash
php artisan migrate
```

2. **Wallet account akan auto-created** pada saat:
    - First time user melakukan top up
    - First time user melakukan payment
    - First time user melakukan withdraw

## ✅ Testing Checklist

- [ ] Buyer bisa top up dan saldo bertambah
- [ ] **Buyer payment dengan ewallet:**
    - [ ] Mengurangi saldo buyer
    - [ ] Create escrow dengan status 'held'
- [ ] **Buyer payment dengan bank_transfer/virtual_account:**
    - [ ] TIDAK mengurangi saldo buyer
    - [ ] TIDAK create escrow
- [ ] Seller lihat escrow dengan status 'held' (dari ewallet payment)
- [ ] **Buyer confirm order dari ewallet:**
    - [ ] Dana release ke seller
    - [ ] Seller saldo bertambah
- [ ] **Buyer confirm order dari bank_transfer:**
    - [ ] Order selesai (tanpa wallet operation)
- [ ] Buyer cancel pending order (tidak ada wallet operation)
- [ ] Buyer cancel paid order (jika ewallet, refund ke buyer)
- [ ] Seller bisa withdraw dengan validasi saldo cukup
- [ ] Ledger entries tercatat dengan benar
- [ ] All operations transactional (tidak ada orphaned data)
- [ ] Withdrawal ID format: wd-YYYYMMDD-XXXX
- [ ] Reference number format konsisten

## 🔗 Integration dengan Existing Endpoints

### PaymentController::pay

✅ Integrated - Memanggil `WalletService::holdFundsForOrder()`

### OrderController::confirm

✅ Integrated - Memanggil `WalletService::releaseFundsToSeller()`

### OrderController::cancel

✅ Integrated - Jika ada escrow held, panggil `WalletService::refundFundsToBuyer()`

## 📌 Notes

- Wallet account di-auto-create pertama kali digunakan (lazy loading)
- Reference number withdraw: `WD-{timestamp}-{padded_user_id}`
- Semua balance operations dalam integer (Rupiah tanpa decimal)
- Held amount calculated dari sum escrow status 'held'
- Ledger immutable (hanya insert, tidak ada update/delete)
