# 🚀 QUICK START - Stock Restoration Testing

## 5 Menit Testing Guide

### Prerequisites
```bash
# Terminal 1: Start MySQL XAMPP
# Start Apache XAMPP
# Terminal 2: Start Laravel
php artisan serve
```

---

## Test Flow (5 Menit)

### 1️⃣ Get Your Tokens (1 min)

Gunakan credentials Anda untuk login:
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"buyer@test.com","password":"password"}'
```

Save `token` dari response sebagai `BUYER_TOKEN`

Lakukan sama untuk seller account, save sebagai `SELLER_TOKEN`

---

### 2️⃣ Create Order (1 min)

```bash
# Set variables
BUYER_TOKEN="your_buyer_token"
SELLER_TOKEN="your_seller_token"
PRODUCT_ID=1
ORDER_QUANTITY=3

# Create order
RESPONSE=$(curl -X POST http://localhost:8000/api/orders \
  -H "Authorization: Bearer $BUYER_TOKEN" \
  -H "Content-Type: application/json" \
  -d "{\"product_id\": $PRODUCT_ID, \"quantity\": $ORDER_QUANTITY}")

# Extract order ID
ORDER_ID=$(echo $RESPONSE | grep -o '"id":[0-9]*' | head -1 | cut -d: -f2)
echo "Order ID: $ORDER_ID"
echo "Product stock after order: $(echo $RESPONSE | grep -o '"stock":[0-9]*' | head -3 | tail -1)"
```

**Expected:**
- Stock berkurang sebanyak quantity yang dipesan

---

### 3️⃣ Request Cancellation (1 min)

```bash
curl -X POST http://localhost:8000/api/orders/$ORDER_ID/cancellation-request \
  -H "Authorization: Bearer $BUYER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"reason":"other","details":"Test stock restoration"}'
```

**Expected:**
- Cancellation request created dengan status: pending

---

### 4️⃣ Approve Cancellation (1 min) ⭐ MAIN TEST

```bash
RESPONSE=$(curl -X PUT http://localhost:8000/api/orders/$ORDER_ID/cancellation-request/approve \
  -H "Authorization: Bearer $SELLER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"seller_notes":"Stock test approval"}')

echo "=== APPROVAL RESPONSE ==="
echo $RESPONSE | jq '.'

echo ""
echo "=== STOCK RESTORED INFO ==="
echo $RESPONSE | jq '.data.stock_restored'

echo ""
echo "=== PRODUCT STOCK IN RESPONSE ==="
echo $RESPONSE | jq '.data.product.stock'
```

**Expected:**
```json
"stock_restored": {
  "product_id": 1,
  "product_name": "...",
  "original_stock": X,
  "new_stock": Y,      // ← Should be higher than original_stock
  "quantity_restored": 3
},
"product": {
  "stock": Y  // ← SAME AS new_stock!
}
```

---

### 5️⃣ Verify in Database (1 min)

```bash
# Login to MySQL
mysql -u root

# Use database
USE bedevbis_marketplace;

# Check product stock
SELECT id, stock FROM products WHERE id=1;

# Check order status
SELECT id, status FROM orders WHERE id=$ORDER_ID;

# Check cancellation status  
SELECT id, status FROM cancellation_requests WHERE order_id=$ORDER_ID;
```

**Expected:**
- Product stock = initial stock (restored)
- Order status = cancelled
- Cancellation status = approved

---

## 🎯 Key Points

1. **Stock DECREASES** saat order dibuat ✓
2. **Stock INCREASES** saat cancellation diapprove ✓
3. **API Response** include restored stock data ✓
4. **Database** reflect changes correctly ✓

---

## ⚠️ Common Issues

| Issue | Check | Fix |
|-------|-------|-----|
| Stock tidak berkurang | Order created? | Check DB order table |
| Stock tidak bertambah | Cancellation approved? | Check DB cancellation_requests |
| Wrong response | Using old controller? | Clear cache: `php artisan config:cache` |
| 403 Forbidden | Seller token? | Use correct seller token |
| 404 Order not found | Order ID correct? | Get order ID dari creation response |

---

## 📝 FE Implementation

Setelah test API berhasil, update FE untuk:

```javascript
// Option 1: Use stock dari response
const restoredStock = response.data.data.product.stock;
setProductStock(restoredStock);

// Option 2: Use full order data
const order = response.data.data.order;
setOrder(order);
```

---

## ✅ Success Criteria

- [ ] Create order: stock berkurang
- [ ] Approve cancellation: stock naik kembali
- [ ] API response include product.stock
- [ ] Database values correct
- [ ] FE can read stock from response
- [ ] UI update menunjukkan stock yang di-restore

---

## 🚀 Next

Setelah test berhasil, update FE components untuk:
1. Show loading state saat approving
2. Display restored stock
3. Update product list UI
4. Show success notification

**Waktu total test: ~5 menit**
