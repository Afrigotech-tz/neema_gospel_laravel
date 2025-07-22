# Products & Payments API Documentation

## Overview
This document provides comprehensive documentation for the complete products and payments API system in the Neema Gospel application.

## Base URL
```
http://localhost:8000/api
```

## Authentication
All protected routes require Bearer token authentication:
```
Authorization: Bearer {token}
```

## Products API

### Public Routes

#### Get All Products
```http
GET /api/products
```
**Query Parameters:**
- `category_id` (optional): Filter by category
- `search` (optional): Search products by name/description
- `active` (optional): Filter active products only
- `sort_by` (optional): Sort field (default: created_at)
- `sort_order` (optional): Sort order (default: desc)
- `per_page` (optional): Items per page (default: 15)

#### Get Single Product
```http
GET /api/products/{id}
```

#### Get Product Categories
```http
GET /api/products/categories/all
```

#### Get Products by Category
```http
GET /api/products/category/{categoryId}
```

### Admin Routes (Protected)

#### Create Product
```http
POST /api/admin/products
```
**Body (multipart/form-data):**
```json
{
  "name": "Product Name",
  "description": "Product description",
  "category_id": 1,
  "base_price": 29.99,
  "sku": "PROD-001",
  "stock_quantity": 100,
  "is_active": true,
  "images": [file1, file2],
  "meta_title": "SEO Title",
  "meta_description": "SEO Description",
  "weight": 1.5,
  "dimensions": {"length": 10, "width": 5, "height": 3},
  "tags": ["tag1", "tag2"]
}
```

#### Update Product
```http
PUT /api/admin/products/{id}
```
**Body:** Same as create product

#### Delete Product
```http
DELETE /api/admin/products/{id}
```

### Product Categories (Admin)

#### Create Category
```http
POST /api/admin/products/categories
```
**Body:**
```json
{
  "name": "Category Name",
  "description": "Category description",
  "parent_id": null,
  "is_active": true,
  "sort_order": 0
}
```

#### Update Category
```http
PUT /api/admin/products/categories/{id}
```

#### Delete Category
```http
DELETE /api/admin/products/categories/{id}
```

### Product Variants (Admin)

#### Create Variant
```http
POST /api/admin/products/variants
```
**Body:**
```json
{
  "product_id": 1,
  "sku": "VAR-001",
  "price": 34.99,
  "stock_quantity": 50,
  "is_active": true,
  "attribute_values": [1, 2, 3]
}
```

#### Update Variant
```http
PUT /api/admin/products/variants/{id}
```

#### Delete Variant
```http
DELETE /api/admin/products/variants/{id}
```

### Product Attributes (Admin)

#### Create Attribute
```http
POST /api/admin/products/attributes
```
**Body:**
```json
{
  "name": "Color",
  "type": "color",
  "is_required": true
}
```

#### Create Attribute Value
```http
POST /api/admin/products/attribute-values
```
**Body:**
```json
{
  "product_attribute_id": 1,
  "value": "Red",
  "color_code": "#FF0000"
}
```

## Cart API

### Get Cart
```http
GET /api/cart
```

### Add to Cart
```http
POST /api/cart
```
**Body:**
```json
{
  "product_id": 1,
  "product_variant_id": 1,
  "quantity": 2
}
```

### Update Cart Item
```http
PUT /api/cart/{id}
```
**Body:**
```json
{
  "quantity": 3
}
```

### Remove from Cart
```http
DELETE /api/cart/{id}
```

### Clear Cart
```http
DELETE /api/cart/clear
```

## Payments API

### Get Payment Methods
```http
GET /api/payments/methods
```

### Process Payment
```http
POST /api/payments/process
```
**Body:**
```json
{
  "address_id": 1,
  "payment_method_id": 1,
  "notes": "Special delivery instructions"
}
```

### Get User Orders
```http
GET /api/payments/orders
```

### Get Order Details
```http
GET /api/payments/orders/{id}
```

### Update Order Status
```http
PUT /api/payments/orders/{id}/status
```
**Body:**
```json
{
  "status": "processing"
}
```

## Advanced Payments API

### Initialize Payment
```http
POST /api/payments/initialize
```
**Body:**
```json
{
  "order_id": 1,
  "payment_method": "stripe",
  "gateway": "stripe"
}
```

### Verify Payment
```http
POST /api/payments/verify
```
**Body:**
```json
{
  "transaction_id": "TXN-ABC123"
}
```

### Get Payment History
```http
GET /api/payments/history
```

### Process Refund
```http
POST /api/payments/refunds
```
**Body:**
```json
{
  "order_id": 1,
  "amount": 29.99,
  "reason": "Product defective",
  "refund_type": "partial"
}
```

### Get Refunds
```http
GET /api/payments/refunds
```

### Get Refund Details
```http
GET /api/payments/refunds/{refundId}
```

## Webhooks

### Stripe Webhook
```http
POST /api/webhooks/stripe
```

### Paystack Webhook
```http
POST /api/webhooks/paystack
```

### Flutterwave Webhook
```http
POST /api/webhooks/flutterwave
```

## Response Format

### Success Response
```json
{
  "success": true,
  "message": "Operation successful",
  "data": {...}
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error message",
  "error": "Detailed error"
}
```

## Status Codes

- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `404` - Not Found
- `422` - Validation Error
- `500` - Server Error

## Payment Statuses

- `pending` - Payment initiated
- `processing` - Payment being processed
- `completed` - Payment successful
- `failed` - Payment failed
- `cancelled` - Payment cancelled
- `refunded` - Payment refunded

## Order Statuses

- `pending` - Order placed
- `processing` - Order being processed
- `shipped` - Order shipped
- `delivered` - Order delivered
- `cancelled` - Order cancelled
- `refunded` - Order refunded

## Environment Variables Required

For payment gateways, add these to your `.env` file:

```bash
# Stripe
STRIPE_PUBLISHABLE_KEY=pk_test_...
STRIPE_SECRET_KEY=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...

# Paystack
PAYSTACK_PUBLIC_KEY=pk_test_...
PAYSTACK_SECRET_KEY=sk_test_...

# Flutterwave
FLUTTERWAVE_PUBLIC_KEY=FLWPUBK_TEST-...
FLUTTERWAVE_SECRET_KEY=FLWSECK_TEST-...
FLUTTERWAVE_SECRET_HASH=your-secret-hash
```

## Testing

### Test Credit Card Numbers (Stripe)
- `4242424242424242` - Visa (Success)
- `4000056655665556` - Visa (Debit)
- `5555555555554444` - Mastercard

### Test Bank Account (Paystack)
- Bank: `011` - First Bank
- Account: `0000000000`

## Rate Limiting

- Public routes: 60 requests per minute
- Authenticated routes: 120 requests per minute
- Payment routes: 30 requests per minute
