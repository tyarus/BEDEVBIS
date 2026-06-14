# Stock Restoration - Frontend Integration Guide

## API Response Changes

### Endpoint: `PUT /api/orders/{id}/cancellation-request/approve`

Ketika seller menyetujui pembatalan, API sekarang mengembalikan data lengkap dengan stock yang sudah di-restore:

```json
{
  "success": true,
  "message": "Permintaan pembatalan disetujui",
  "data": {
    "id": 1,
    "status": "cancelled",
    "refunded_amount": 100000,
    "refunded_to_wallet": true,
    "stock_restored": {
      "product_id": 12,
      "product_name": "adasdasda",
      "original_stock": 0,
      "new_stock": 1,
      "quantity_restored": 1
    },
    "product": {
      "id": 12,
      "name": "adasdasda",
      "stock": 1    // ← STOCK YANG SUDAH DI-RESTORE
    },
    "order": {
      "id": 1,
      "status": "cancelled",
      "product": {
        "id": 12,
        "name": "adasdasda",
        "stock": 1
      },
      // ... order data lainnya
    }
  }
}
```

## Frontend Implementation

### Option 1: Update State dengan Stock dari Response

```javascript
// Di handler approval cancellation
const handleApproveCancel = async (orderId) => {
  try {
    const response = await axios.put(
      `/api/orders/${orderId}/cancellation-request/approve`,
      { seller_notes: 'Disetujui' },
      { headers: { Authorization: `Bearer ${token}` } }
    );

    // Update product stock dari response
    if (response.data.data.product) {
      setProductStock(response.data.data.product.stock);
    }

    // Atau update dari full order data
    if (response.data.data.order) {
      setCurrentOrder(response.data.data.order);
    }

    // Show success message
    showNotification('Pembatalan disetujui. Stok produk telah dipulihkan!');
  } catch (error) {
    handleError(error);
  }
};
```

### Option 2: Refresh Product Data dari API

```javascript
const handleApproveCancel = async (orderId, productId) => {
  try {
    // Approve cancellation
    const approveResponse = await axios.put(
      `/api/orders/${orderId}/cancellation-request/approve`,
      { seller_notes: 'Disetujui' },
      { headers: { Authorization: `Bearer ${token}` } }
    );

    // Refresh product data untuk memastikan stock terbaru
    const productResponse = await axios.get(
      `/api/products/${productId}`,
      { headers: { Authorization: `Bearer ${token}` } }
    );

    // Update state dengan data terbaru
    setProductStock(productResponse.data.data.stock);
    
    showNotification('Pembatalan disetujui. Stok produk telah dipulihkan!');
  } catch (error) {
    handleError(error);
  }
};
```

### Option 3: Refetch Product List

```javascript
const handleApproveCancel = async (orderId) => {
  try {
    // Approve cancellation
    await axios.put(
      `/api/orders/${orderId}/cancellation-request/approve`,
      { seller_notes: 'Disetujui' },
      { headers: { Authorization: `Bearer ${token}` } }
    );

    // Refetch seluruh product list
    await refetchProducts();
    
    showNotification('Pembatalan disetujui. Stok produk telah dipulihkan!');
  } catch (error) {
    handleError(error);
  }
};
```

## Validation Checklist

Ketika test stock restoration, pastikan:

- [ ] Create order → Stock berkurang ✓
- [ ] Check database → Stock benar-benar berkurang ✓
- [ ] Approve cancellation → Response include updated stock ✓
- [ ] Check database → Stock naik kembali ✓
- [ ] FE update state dengan stock dari response ATAU refresh data ✓
- [ ] UI menampilkan stock yang sudah di-restore ✓

## Database Status

Jika ingin verify langsung di database:

```sql
-- Check product stock setelah cancellation approved
SELECT 
    p.id,
    p.name,
    p.stock,
    o.id as order_id,
    o.quantity,
    cr.status as cancellation_status
FROM products p
LEFT JOIN orders o ON p.id = o.product_id
LEFT JOIN cancellation_requests cr ON cr.order_id = o.id
WHERE o.status = 'cancelled'
ORDER BY cr.updated_at DESC
LIMIT 10;
```

## Common Issues

### Issue: Stock masih berkurang setelah approval

**Kemungkinan Penyebab:**
1. FE tidak refresh data → Solution: Use stock dari response atau refetch
2. FE punya cache yang tidak clear → Solution: Clear cache setelah approval
3. API response tidak diparse dengan benar → Solution: Check console log response

### Issue: Response tidak include stock

Pastikan:
1. Sudah update ke controller terbaru ✓
2. Clear Laravel cache: `php artisan config:cache` ✓
3. Restart server jika perlu

## Testing dengan cURL

```bash
# Test approval endpoint
curl -X PUT http://localhost:8000/api/orders/1/cancellation-request/approve \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"seller_notes":"Approved"}'

# Check response include product stock
```

---

**Last Updated:** 2026-06-01  
**Status:** ✅ Stock Restoration Working
