# BE to FE Integration Notes (BeDevbis API)

Dokumen ini merangkum kontrak backend Laravel untuk kebutuhan frontend.

## 1) Base URL per Environment

- Local:
    - `http://localhost:8000/api`
- Staging:
    - `https://<staging-domain>/api` (belum hardcoded di repo, set lewat env deploy)
- Production (Railway):
    - `https://<RAILWAY_PUBLIC_DOMAIN>/api`

Contoh env backend:

```env
APP_URL=https://${RAILWAY_PUBLIC_DOMAIN}
FRONTEND_URL=https://your-frontend-domain.com
```

## 2) Header Auth

Untuk endpoint protected, kirim:

```http
Authorization: Bearer <token>
Accept: application/json
Content-Type: application/json
```

## 3) Daftar Endpoint Lengkap

### Public

- `GET /api/health`
- `POST /api/auth/register` (rate limit)
- `POST /api/auth/login` (rate limit)
- `GET /api/products`
- `GET /api/products/{id}`

### Protected (butuh Bearer token)

- `POST /api/auth/logout`
- `GET /api/me`
- `POST /api/products` (seller)
- `PUT /api/products/{id}` (owner product)
- `DELETE /api/products/{id}` (owner product)
- `GET /api/seller/products` (seller list by auth user id)
- `POST /api/orders` (buyer)
- `GET /api/orders` (buyer own orders)
- `GET /api/orders/{id}` (buyer/seller pemilik order)
- `GET /api/seller/orders` (seller own orders)
- `PUT /api/seller/orders/{id}/ship` (seller pemilik order)
- `PUT /api/orders/{id}/confirm` (buyer pemilik order)
- `PUT /api/orders/{id}/cancel` (buyer pemilik order)
- `POST /api/orders/{id}/pay` (buyer pemilik order)
- `GET /api/orders/{id}/transaction-chat` (buyer/seller pemilik order)
- `POST /api/orders/{id}/transaction-chat/messages` (buyer/seller pemilik order)
- `PUT /api/orders/{id}/transaction-chat/checklist` (buyer/seller pemilik order)
- `PUT /api/orders/{id}/transaction-chat/status` (buyer/seller pemilik order)
- `POST /api/orders/{id}/transaction-chat/completion-code` (seller pemilik order)
- `POST /api/orders/{id}/transaction-chat/verify-completion-code` (buyer pemilik order)

## 4) Request Body + Success Response per Endpoint

## Auth

### `POST /api/auth/register`

Request:

```json
{
    "name": "John Buyer",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "role": "buyer"
}
```

Success `201`:

```json
{
    "success": true,
    "message": "Registrasi berhasil",
    "data": {
        "user": {
            "id": 1,
            "name": "John Buyer",
            "email": "john@example.com",
            "role": "buyer",
            "created_at": "2026-04-27T00:00:00.000000Z"
        },
        "token": "1|..."
    }
}
```

### `POST /api/auth/login`

Request:

```json
{
    "email": "john@example.com",
    "password": "password123"
}
```

Success `200`:

```json
{
    "success": true,
    "message": "Login berhasil",
    "data": {
        "user": {
            "id": 1,
            "name": "John Buyer",
            "email": "john@example.com",
            "role": "buyer",
            "created_at": "2026-04-27T00:00:00.000000Z"
        },
        "token": "1|..."
    }
}
```

### `POST /api/auth/logout`

Request body: none

Success `200`:

```json
{
    "success": true,
    "message": "Logout berhasil",
    "data": null
}
```

### `GET /api/me`

Request body: none

Success `200`:

```json
{
    "success": true,
    "message": "Data user berhasil diambil",
    "data": {
        "id": 1,
        "name": "John Buyer",
        "email": "john@example.com",
        "role": "buyer",
        "created_at": "2026-04-27T00:00:00.000000Z"
    }
}
```

## Products

### `GET /api/products`

Query params:

- `search` (optional)
- `min_price` (optional, efektif jika `max_price` juga ada)
- `max_price` (optional, efektif jika `min_price` juga ada)
- `page` (optional)

Success `200`:

```json
{
    "success": true,
    "message": "Daftar produk berhasil diambil",
    "data": [
        {
            "id": 1,
            "seller_id": 2,
            "name": "Laptop Pro 14",
            "description": "Laptop untuk kerja dan editing.",
            "price": 14500000,
            "stock": 12,
            "image_url": "https://...",
            "status": "active",
            "game_category": "mobile_legends",
            "login_method": "facebook",
            "created_at": "2026-04-27T00:00:00.000000Z",
            "updated_at": "2026-04-27T00:00:00.000000Z"
        }
    ],
    "pagination": {
        "total": 4,
        "per_page": 12,
        "current_page": 1,
        "last_page": 1
    }
}
```

### `GET /api/products/{id}`

Success `200`:

```json
{
    "success": true,
    "message": "Detail produk berhasil diambil",
    "data": {
        "id": 1,
        "seller_id": 2,
        "name": "Laptop Pro 14",
        "description": "Laptop untuk kerja dan editing.",
        "price": 14500000,
        "stock": 12,
        "image_url": "https://...",
        "status": "active",
        "game_category": "mobile_legends",
        "login_method": "facebook",
        "created_at": "2026-04-27T00:00:00.000000Z",
        "updated_at": "2026-04-27T00:00:00.000000Z"
    }
}
```

### `POST /api/products` (seller)

Request:

```json
{
    "name": "Laptop Gaming",
    "description": "High performance",
    "price": 15000000,
    "stock": 10,
    "image_url": "https://example.com/laptop.jpg",
    "status": "active",
    "game_category": "mobile_legends",
    "login_method": "facebook"
}
```

Success `201`:

```json
{
    "success": true,
    "message": "Produk berhasil dibuat",
    "data": {
        "id": 10,
        "seller_id": 2,
        "name": "Laptop Gaming",
        "description": "High performance",
        "price": 15000000,
        "stock": 10,
        "image_url": "https://example.com/laptop.jpg",
        "status": "active",
        "game_category": "mobile_legends",
        "login_method": "facebook",
        "created_at": "2026-04-27T00:00:00.000000Z",
        "updated_at": "2026-04-27T00:00:00.000000Z"
    }
}
```

### `PUT /api/products/{id}` (owner)

Request (semua field optional):

```json
{
    "name": "Laptop Gaming Pro",
    "price": 15500000,
    "stock": 8
}
```

Success `200`:

```json
{
    "success": true,
    "message": "Produk berhasil diperbarui",
    "data": {
        "id": 10,
        "seller_id": 2,
        "name": "Laptop Gaming Pro",
        "price": 15500000,
        "stock": 8,
        "status": "active"
    }
}
```

### `DELETE /api/products/{id}` (owner)

Success `200`:

```json
{
    "success": true,
    "message": "Produk berhasil dihapus",
    "data": null
}
```

### `GET /api/seller/products`

Query params:

- `search` (optional)
- `page` (optional)

Success `200`: format sama dengan `GET /api/products` + pagination.

## Orders

### `POST /api/orders` (buyer)

Request:

```json
{
    "product_id": 1,
    "quantity": 2
}
```

Success `201`:

```json
{
    "success": true,
    "message": "Order berhasil dibuat",
    "data": {
        "id": 1,
        "buyer_id": 1,
        "product_id": 1,
        "seller_id": 2,
        "quantity": 2,
        "total_price": 29000000,
        "status": "pending_payment",
        "tracking_number": null
    }
}
```

### `GET /api/orders` (buyer own)

Success `200`: `data` array order + `pagination`.

### `GET /api/orders/{id}` (buyer/seller pemilik order)

Success `200`: detail order termasuk relasi jika available (`buyer`, `seller`, `product`, `payments`).

### `GET /api/seller/orders` (seller own)

Success `200`: `data` array order + `pagination`.

### `PUT /api/seller/orders/{id}/ship` (seller pemilik order)

Request:

```json
{
    "tracking_number": "TRX-SHIP-0001"
}
```

Success `200`:

```json
{
    "success": true,
    "message": "Order berhasil dikirim",
    "data": {
        "id": 1,
        "status": "shipped",
        "tracking_number": "TRX-SHIP-0001"
    }
}
```

### `PUT /api/orders/{id}/confirm` (buyer pemilik order)

Request body: none

Success `200`:

```json
{
    "success": true,
    "message": "Order berhasil dikonfirmasi, dana di-release ke seller",
    "data": {
        "id": 1,
        "status": "completed"
    }
}
```

Catatan: endpoint ini menerima order status `shipped` atau `delivered`.

### `PUT /api/orders/{id}/cancel` (buyer pemilik order)

Request body: none

Success `200`:

```json
{
    "success": true,
    "message": "Order berhasil dibatalkan",
    "data": {
        "id": 1,
        "status": "cancelled"
    }
}
```

## Payments

### `POST /api/orders/{id}/pay` (buyer pemilik order)

Request:

```json
{
    "payment_method": "bank_transfer"
}
```

Nilai `payment_method` valid:

- `bank_transfer`
- `virtual_account`
- `ewallet`

Success `201`:

```json
{
    "success": true,
    "message": "Pembayaran berhasil diproses",
    "data": {
        "id": 1,
        "order_id": 1,
        "amount": 29000000,
        "method": "bank_transfer",
        "status": "success",
        "paid_at": "2026-04-27T00:00:00.000000Z",
        "created_at": "2026-04-27T00:00:00.000000Z"
    }
}
```

## 5) Format Error Penting

## `401 Unauthorized`

Login gagal:

```json
{
    "success": false,
    "message": "Email atau password salah",
    "errors": []
}
```

Token tidak valid/expired/revoked (middleware Sanctum):

```json
{
    "message": "Unauthenticated."
}
```

## `422 Validation Error` (default Laravel FormRequest)

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "email": ["Email sudah terdaftar"],
        "password": ["Password minimal 8 karakter"]
    }
}
```

## `429 Too Many Requests` (throttle login/register)

```json
{
    "message": "Too Many Attempts."
}
```

## `500 Internal Server Error` (catch di controller)

```json
{
    "success": false,
    "message": "Terjadi kesalahan",
    "errors": {
        "error": "detail internal error"
    }
}
```

## 6) Aturan Auth dan Token

- Auth menggunakan Laravel Sanctum personal access token.
- Login/Register menghasilkan token baru.
- Token dikirim sebagai `Bearer token`.
- Expiry token dikontrol env `SANCTUM_TOKEN_EXPIRY_DAYS` (default repo: `7` hari).
- Logout hanya revoke token aktif saat ini (`currentAccessToken()->delete()`).
- Token jadi tidak valid jika:
    - expired,
    - di-revoke via logout,
    - tidak dikirim/format salah.

## 7) Protected Endpoint + Role Matrix

- Seller only:
    - `POST /api/products` (dicek di `CreateProductRequest::authorize`)
    - `PUT /api/products/{id}` (harus owner product)
    - `DELETE /api/products/{id}` (harus owner product)
    - `GET /api/seller/products` (data berdasarkan `seller_id = auth_user.id`)
    - `GET /api/seller/orders` (data berdasarkan `seller_id = auth_user.id`)
    - `PUT /api/seller/orders/{id}/ship` (harus seller pemilik order)
    - `POST /api/orders/{id}/transaction-chat/completion-code` (harus seller pemilik order)
- Buyer only:
    - `POST /api/orders` (dicek di `CreateOrderRequest::authorize`)
    - `GET /api/orders` (buyer own)
    - `PUT /api/orders/{id}/confirm` (buyer pemilik order)
    - `PUT /api/orders/{id}/cancel` (buyer pemilik order)
    - `POST /api/orders/{id}/pay` (buyer pemilik order)
    - `POST /api/orders/{id}/transaction-chat/verify-completion-code` (buyer pemilik order)
- Buyer/Seller pemilik order:
    - `GET /api/orders/{id}`
    - `GET /api/orders/{id}/transaction-chat`
    - `POST /api/orders/{id}/transaction-chat/messages`
    - `PUT/PATCH /api/orders/{id}/transaction-chat/checklist`
    - `PUT/PATCH /api/orders/{id}/transaction-chat/status`

## 8) Aturan Validasi Penting

### Register

- `name`: required, string, max 255
- `email`: required, email, unique
- `password`: required, string, min 8, confirmed
- `role`: required, `seller|buyer`

### Login

- `email`: required, email
- `password`: required, string

### Create Product

- `name`: required, string, max 255
- `description`: nullable, string
- `game_category`: nullable, `mobile_legends|pubg_mobile|free_fire|efootball|fifa_26`
- `login_method`: nullable, `facebook|google|x|konami_id|ea`
- `price`: required, numeric, min 0.01
- `stock`: required, integer, min 0
- `image_url`: nullable, url
- `status`: nullable, `active|inactive`

### Update Product

- semua field nullable, rule sama dengan create

### Create Order

- `product_id`: required, exists `products.id`
- `quantity`: required, integer, min 1

### Ship Order

- `tracking_number`: required, string

### Pay Order

- `payment_method`: required, `bank_transfer|virtual_account|ewallet`

## 9) Pagination / Filter / Sort

- Semua list endpoint pakai `paginate(12)`:
    - `GET /api/products`
    - `GET /api/seller/products`
    - `GET /api/orders`
    - `GET /api/seller/orders`
- Bentuk pagination:

```json
{
    "pagination": {
        "total": 100,
        "per_page": 12,
        "current_page": 1,
        "last_page": 9
    }
}
```

- Filter saat ini:
    - `GET /api/products`
        - `search` (name/description)
        - `min_price` + `max_price`
        - `page`
    - `GET /api/seller/products`
        - `search`
        - `page`
- Sorting:
    - Belum ada query sort eksplisit di endpoint (mengikuti default query DB).

## 10) CORS yang Diizinkan

Default `config/cors.php`:

- `http://localhost:3000`
- `http://localhost:8000`
- nilai env `FRONTEND_URL` (default local: `http://localhost:3000`)

Setting lain:

- `allowed_methods`: `*`
- `allowed_headers`: `*`
- `supports_credentials`: `true`

## 11) Rate Limit Endpoint Penting

- `POST /api/auth/register`: `throttle:5,1` (max 5 request/menit/IP)
- `POST /api/auth/login`: `throttle:5,1` (max 5 request/menit/IP)

Catatan:

- Env `API_RATE_LIMIT_LOGIN=5` ada, tapi saat ini route memakai nilai hardcoded `throttle:5,1`.

## 12) Akun Test Seeder (Dummy)

Dari `DatabaseSeeder`:

- Buyer:
    - email: `tyar@example.com`
    - password: `password123`
- Seller:
    - email: `tyars@example.com`
    - password: `password123`

Jalankan seed:

```bash
php artisan migrate:fresh --seed
```

## 13) Transaction Chat + Fraud Tracking (NEW)

Fitur transaksi game membutuhkan sistem chat, checklist, dan audit log untuk melacak setiap aktivitas dalam transaksi.

### Endpoint Transaction Chat (Protected)

#### `GET /api/orders/{id}/transaction-chat`

Success `200`:

```json
{
    "success": true,
    "message": "Data transaksi chat berhasil diambil",
    "data": {
        "order_id": 1,
        "status": "chat_open",
        "checklist": {
            "account_match": false,
            "account_secured": false,
            "seller_device_removed": false,
            "completion_code_verified": false
        },
        "completion_code": null,
        "completion_code_expires_at": null,
        "completion_code_verified_at": null,
        "messages": [],
        "activities": [],
        "updated_at": "2026-05-18T12:00:00.000000Z"
    }
}
```

#### `POST /api/orders/{id}/transaction-chat/messages`

Request:

```json
{
    "message": "Akun sudah saya cek, datanya sesuai.",
    "message_type": "text"
}
```

Valid `message_type`:

- `text`
- `system`
- `checklist_update`
- `status_update`
- `completion_code`

Success `201`:

```json
{
    "success": true,
    "message": "Pesan berhasil dikirim",
    "data": {
        "id": 1,
        "message": "Akun sudah saya cek, datanya sesuai.",
        "message_type": "text",
        "sender_id": 2,
        "sender_role": "seller",
        "created_at": "2026-05-18T12:00:00.000000Z"
    }
}
```

#### `PUT/PATCH /api/orders/{id}/transaction-chat/checklist`

Request:

```json
{
    "account_match": true,
    "account_secured": true,
    "seller_device_removed": false
}
```

Catatan: semua field optional. Checklist field:

- `account_match`
- `account_secured`
- `seller_device_removed`
- `completion_code_verified` (di-set backend setelah verify code sukses)

Success `200`:

```json
{
    "success": true,
    "message": "Checklist berhasil diperbarui",
    "data": {
        "account_match": true,
        "account_secured": true,
        "seller_device_removed": false,
        "completion_code_verified": false
    }
}
```

#### `PUT/PATCH /api/orders/{id}/transaction-chat/status`

Request:

```json
{
    "status": "account_verification"
}
```

Valid `status`:

- `chat_open`
- `account_verification`
- `account_secured`
- `device_cleanup`
- `awaiting_completion_code`
- `completed`
- `disputed`

Success `200`:

```json
{
    "success": true,
    "message": "Status berhasil diperbarui",
    "data": {
        "status": "account_verification",
        "updated_at": "2026-05-18T12:05:00.000000Z"
    }
}
```

#### `POST /api/orders/{id}/transaction-chat/completion-code`

Request body: none (hanya seller yang bisa generate)

Success `201`:

```json
{
    "success": true,
    "message": "Kode penyelesaian berhasil dibuat",
    "data": {
        "completion_code": "ABCD-1234",
        "expires_at": "2026-05-19T10:00:00.000000Z"
    }
}
```

Catatan:

- Code format: `XXXX-XXXX` (8 karakter dengan dash).
- Code hash disimpan di DB, raw code hanya dikirim sekali.
- Berlaku 24 jam.
- Hanya seller (penjual) yang bisa membuat.
- Code digenerate secara random dengan kombinasi huruf & angka.

#### `POST /api/orders/{id}/transaction-chat/verify-completion-code`

Request:

```json
{
    "code": "ABCD-1234"
}
```

Format kode: `XXXX-XXXX` (8 karakter dengan dash) atau `XXXXXXXX` (8 karakter tanpa dash)

Success `200`:

```json
{
    "success": true,
    "message": "Kode valid",
    "data": {
        "verified": true,
        "status": "completed",
        "verified_at": "2026-05-19T10:10:00.000000Z"
    }
}
```

Catatan:

- Hanya buyer (pembeli) yang bisa verify.
- Kode bisa disubmit dengan atau tanpa dash (ABCD-1234 atau ABCD1234).
- Mencegah brute force (rate limit recommended).
- Secara otomatis set `completion_code_verified` di checklist ke `true`.

### Struktur Database

#### `order_transaction_chats`

- `id`: primary key
- `order_id`: FK ke orders
- `status`: enum (chat_open, account_verification, account_secured, device_cleanup, awaiting_completion_code, completed, disputed)
- `completion_code_hash`: hash code (bcrypt)
- `completion_code_expires_at`: timestamp
- `completion_code_verified_at`: timestamp
- `created_at`, `updated_at`

#### `order_transaction_checklists`

- `id`: primary key
- `order_id`: FK ke orders
- `account_match`: boolean
- `account_secured`: boolean
- `seller_device_removed`: boolean
- `completion_code_verified`: boolean
- `updated_by`: FK ke users (who last updated)
- `updated_at`

#### `order_transaction_messages`

- `id`: primary key
- `order_id`: FK ke orders
- `sender_id`: FK ke users
- `sender_role`: enum (buyer, seller)
- `message`: text
- `message_type`: enum (text, system, checklist_update, status_update, completion_code)
- `metadata`: json (optional, untuk data tambahan)
- `created_at`

#### `order_transaction_activities`

- `id`: primary key
- `order_id`: FK ke orders
- `actor_id`: FK ke users (nullable untuk system actions)
- `actor_role`: enum (buyer, seller, admin, system)
- `action`: string (immutable action type: transaction_chat_opened, message_sent, status_changed, dll)
- `description`: text (deskripsi lebih detail)
- `ip_address`: IP pengguna yang melakukan aksi
- `user_agent`: browser/client info
- `metadata`: json (optional, untuk data tambahan)
- `created_at` (immutable, append-only log)

### Keamanan & Anti-Fraud

- **Activity Log Immutable**: Semua aktivitas di-log append-only, tidak bisa dihapus/diubah (audit trail).
- **Hashed Completion Code**: Code di-hash dengan bcrypt, raw code tidak tersimpan di DB.
- **IP & User Agent Tracking**: Setiap aktivitas mencatat IP dan user agent untuk investigasi.
- **Rate Limiting**: Endpoint verify code perlu rate limit untuk mencegah brute force.
- **Code Expiry**: Completion code berlaku 24 jam.
- **Seller-Only Generate**: Hanya seller yang bisa generate kode.
- **Buyer-Only Verify**: Hanya buyer yang bisa verify kode.
