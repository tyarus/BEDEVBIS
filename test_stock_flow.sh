#!/bin/bash

# Test script untuk stock restoration flow

API_URL="http://localhost:8000/api"
BUYER_TOKEN="your_buyer_token_here"
SELLER_TOKEN="your_seller_token_here"
PRODUCT_ID=1
QUANTITY=2

echo "=== Testing Stock Restoration Flow ==="
echo ""

# 1. Check initial stock
echo "1. Checking initial product stock..."
curl -s "$API_URL/products/$PRODUCT_ID" | jq '.data.stock'
echo ""

# 2. Create order
echo "2. Creating order..."
CREATE_ORDER=$(curl -s -X POST "$API_URL/orders" \
  -H "Authorization: Bearer $BUYER_TOKEN" \
  -H "Content-Type: application/json" \
  -d "{\"product_id\": $PRODUCT_ID, \"quantity\": $QUANTITY}")

ORDER_ID=$(echo $CREATE_ORDER | jq '.data.id')
echo "Order ID: $ORDER_ID"
echo "Stock after order: $(echo $CREATE_ORDER | jq '.data.product.stock')"
echo ""

# 3. Check product stock after order
echo "3. Checking product stock after order..."
curl -s "$API_URL/products/$PRODUCT_ID" | jq '.data.stock'
echo ""

# 4. Request cancellation
echo "4. Creating cancellation request..."
curl -s -X POST "$API_URL/orders/$ORDER_ID/cancellation-request" \
  -H "Authorization: Bearer $BUYER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"reason": "other", "details": "Test cancellation"}' | jq '.data.status'
echo ""

# 5. Approve cancellation
echo "5. Approving cancellation..."
APPROVE=$(curl -s -X PUT "$API_URL/orders/$ORDER_ID/cancellation-request/approve" \
  -H "Authorization: Bearer $SELLER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"seller_notes": "Approved"}')

echo "Cancellation status: $(echo $APPROVE | jq '.data.status')"
echo "Stock restored info: $(echo $APPROVE | jq '.data.stock_restored')"
echo "Product stock in response: $(echo $APPROVE | jq '.data.product.stock')"
echo ""

# 6. Final check
echo "6. Final product stock check..."
curl -s "$API_URL/products/$PRODUCT_ID" | jq '.data.stock'
echo ""

echo "=== Flow Complete ==="
