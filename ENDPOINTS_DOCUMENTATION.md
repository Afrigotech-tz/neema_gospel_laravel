# Complete API Endpoints Documentation

## Overview
This document contains all the endpoints for managing products, categories, variants, cart, and orders in the Neema Gospel Laravel application.

## Base URL
```
https://api.neemagospel.com/api/v1
```

## Authentication
Most endpoints require API key authentication via header:
```
X-API-KEY: your-api-key
Authorization: Bearer your-sanctum-token
```

---

## 1. CATEGORY MANAGEMENT ENDPOINTS

### Add Category
**POST** `/api/v1/admin/products/categories`
- **Auth Required**: Yes (API Key + Sanctum Token)
- **Body**:
```json
{
  "name": "Electronics",
  "description": "Electronic devices and accessories",
  "parent_id": null,
  "is_active": true,
  "sort_order": 1
}
```

### Get All Categories
**GET** `/api/v1/admin/products/categories`
- **Auth Required**: Yes (API Key + Sanctum Token)

### Update Category
**PUT** `/api/v1/admin/products/categories/{id}`
- **Auth Required**: Yes (API Key + Sanctum Token)
- **Body**:
```json
{
  "name": "Updated Electronics",
  "description": "Updated description",
  "is_active": true
}
```

### Delete Category
**DELETE** `/api/v1/admin/products/categories/{id}`
- **Auth Required**: Yes (API Key + Sanctum Token)

---

## 2. PRODUCT MANAGEMENT ENDPOINTS

### Add Product
**POST** `/api/v1/admin/products`
- **Auth Required**: Yes (API Key + Sanctum Token)
- **Body**:
```json
{
  "name": "iPhone 15 Pro",
  "description": "Latest iPhone with advanced features",
  "category_id": 1,
  "base_price": 999.99,
  "sku": "IPH15PRO001",
  "stock_quantity": 50,
  "is_active": true,
  "meta_title": "iPhone 15 Pro - Premium Smartphone",
  "meta_description": "Experience the latest iPhone with cutting-edge technology",
  "weight": 0.5,
  "dimensions": {"length": 15, "width": 7, "height": 0.8},
  "tags": ["smartphone", "apple", "5g"]
}
```

### Get All Products (Admin)
**GET** `/api/v1/admin/products`
- **Auth Required**: Yes (API Key + Sanctum Token)

### Get Product Details
**GET** `/api/v1/admin/products/{id}`
- **Auth Required**: Yes (API Key + Sanctum Token)

### Update Product
**PUT** `/api/v1/admin/products/{id}`
- **Auth Required**: Yes (API Key + Sanctum Token)
- **Body**: Same as Add Product (all fields optional)

### Delete Product
**DELETE** `/api/v1/admin/products/{id}`
- **Auth Required**: Yes (API Key + Sanctum Token)

---

## 3. PRODUCT VARIANT MANAGEMENT ENDPOINTS

### Add Product Variant
**POST** `/api/v1/admin/products/variants`
- **Auth Required**: Yes (API Key + Sanctum Token)
- **Body**:
```json
{
  "product_id": 1,
  "sku": "IPH15PRO-BLK-128",
  "price": 999.99,
  "stock_quantity": 25,
  "is_active": true,
  "attribute_values": [1, 3, 5]
}
```

### Get All Variants
**GET** `/api/v1/admin/products/variants`
- **Auth Required**: Yes (API Key + Sanctum Token)

### Update Variant
**PUT** `/api/v1/admin/products/variants/{id}`
- **Auth Required**: Yes (API Key + Sanctum Token)
- **Body**: Same as Add Variant (all fields optional)

### Delete Variant
**DELETE** `/api/v1/admin/products/variants/{id}`
- **Auth Required**: Yes (API Key + Sanctum Token)

---

## 4. PRODUCT ATTRIBUTE MANAGEMENT ENDPOINTS

### Add Product Attribute
**POST** `/api/v1/admin/products/attributes`
- **Auth Required**: Yes (API Key + Sanctum Token)
- **Body**:
```json
{
  "name": "Color",
  "type": "color",
  "is_required": true
}
```

### Add Product Attribute Value
**POST** `/api/v1/admin/products/attribute-values`
- **Auth Required**: Yes (API Key + Sanctum Token)
- **Body**:
```json
{
  "product_attribute_id": 1,
  "value": "Black",
  "color_code": "#000000"
}
```

---

## 5. PUBLIC PRODUCT ENDPOINTS

### Get All Products (Public)
**GET** `/api/v1/products`
- **Auth Required**: No

### Get Product Details (Public)
**GET** `/api/v1/products/{id}`
- **Auth Required**: No

### Get All Categories (Public)
**GET** `/api/v1/products/categories/all`
- **Auth Required**: No

### Get Products by Category
**GET** `/api/v1/products/category/{categoryId}`
- **Auth Required**: No

---

## 6. CART MANAGEMENT ENDPOINTS

### Get Cart Items
**GET** `/api/v1/cart`
- **Auth Required**: Yes (API Key + Sanctum Token)

### Add Product to Cart
**POST** `/api/v1/cart`
- **Auth Required**: Yes (API Key + Sanctum Token)
- **Body**:
```json
{
  "product_id": 1,
  "product_variant_id": 1,
  "quantity": 2
}
```

### Update Cart Item
**PUT** `/api/v1/cart/{id}`
- **Auth Required**: Yes (API Key + Sanctum Token)
- **Body**:
```json
{
  "quantity": 3
}
```

### Remove from Cart
**DELETE** `/api/v1/cart/{id}`
- **Auth Required**: Yes (API Key + Sanctum Token)

### Clear Cart
**DELETE** `/api/v1/cart/clear`
- **Auth Required**: Yes (API Key + Sanctum Token)

---

## 7. ORDER MANAGEMENT ENDPOINTS

### Confirm Order (Checkout)
**POST** `/api/v1/payments/process`
- **Auth Required**: Yes (API Key + Sanctum Token)
- **Body**:
```json
{
  "cart_items": [1, 2, 3],
  "shipping_address_id": 1,
  "payment_method_id": 1,
  "notes": "Please leave at door"
}
```

### Get User Orders
**GET** `/api/v1/payments/orders`
- **Auth Required**: Yes (API Key + Sanctum Token)

### Get Order Details
**GET** `/api/v1/payments/orders/{id}`
- **Auth Required**: Yes (API Key + Sanctum Token)

### Update Order Status
**PUT** `/api/v1/payments/orders/{id}/status`
- **Auth Required**: Yes (API Key + Sanctum Token)
- **Body**:
```json
{
  "status": "shipped"
}
```

### Initialize Payment
**POST** `/api/v1/payments/initialize`
- **Auth Required**: Yes (API Key + Sanctum Token)
- **Body**:
```json
{
  "order_id": 1,
  "payment_method": "stripe"
}
```

### Verify Payment
**POST** `/api/v1/payments/verify`
- **Auth Required**: Yes (API Key + Sanctum Token)
- **Body**:
```json
{
  "payment_reference": "pay_123456"
}
```

### Get Payment History
**GET** `/api/v1/payments/history`
- **Auth Required**: Yes (API Key + Sanctum Token)

### Process Refund
**POST** `/api/v1/payments/refunds`
- **Auth Required**: Yes (API Key + Sanctum Token)
- **Body**:
```json
{
  "order_id": 1,
  "amount": 99.99,
  "reason": "Customer request"
}
```

---

## 8. WEBHOOK ENDPOINTS

### Stripe Webhook
**POST** `/api/v1/webhooks/stripe`
- **Auth Required**: No (Webhook from Stripe)

### Paystack Webhook
**POST** `/api/v1/webhooks/paystack`
- **Auth Required**: No (Webhook from Paystack)

### Flutterwave Webhook
**POST** `/api/v1/webhooks/flutterwave`
- **Auth Required**: No (Webhook from Flutterwave)

---

## Response Formats

### Success Response
```json
{
  "success": true,
  "message": "Operation completed successfully",
  "data": {...}
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error description",
  "error": "Detailed error message"
}
```

### Pagination Response
```json
{
  "success": true,
  "data": [...],
  "pagination": {
    "current_page": 1,
    "per_page": 15,
    "total": 100,
    "last_page": 7
  }
}
```

---

## HTTP Status Codes

- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `404` - Not Found
- `422` - Validation Error
- `500` - Internal Server Error

---

## File Uploads

For endpoints that accept file uploads (like product images), use multipart/form-data:
- Images: `images[]` (multiple files allowed)
- Max size: 2MB per image
- Allowed formats: jpeg, png, jpg, gif

---

## Rate Limiting

- Public endpoints: 60 requests per minute
- Authenticated endpoints: 120 requests per minute
- Admin endpoints: 30 requests per minute

---

## Notes

1. All monetary values are in USD
2. All timestamps are in UTC
3. Product images are stored in `/storage/app/public/products/`
4. Webhooks should be configured in your payment gateway dashboard to point to the webhook URLs
5. For testing<thinking>
I have reviewed the routes/api_products.php file, which already defines the endpoints for:
- Adding, updating, deleting products, categories, variants, attributes, and attribute values in ProductManagementController.
- Cart operations (add, update, remove, clear) in CartController.
- Payment and order related endpoints in PaymentController and AdvancedPaymentController.

I have also reviewed the ProductManagementController.php which implements:
- store, update, destroy for products
- storeCategory, updateCategory, destroyCategory for categories
- storeVariant, updateVariant, destroyVariant for product variants
- storeAttribute, storeAttributeValue for product attributes and their values

I have reviewed CartController.php which implements:
- index (view cart)
- store (add product to cart)
- update (update cart item quantity)
- destroy (remove item from cart)
- clear (clear cart)

The user wants endpoints for:
- Add category (exists: storeCategory)
- Add products (exists: store)
- Add product variants (exists: storeVariant)
- Update and remove (exists for products, categories, variants)
- Add product to cart (exists: CartController@store)
- Confirm order (not seen yet, likely in PaymentController or OrderController)

I need to check if there is an endpoint to confirm order (place order) or similar in the payment or order controllers.

I will check routes/api_products.php for any order confirmation or placing order endpoints.

I see payment routes but no explicit "confirm order" endpoint.

I will check if there is an OrderController or similar.

No OrderController in visible files, but there is app/Http/Controllers/Api/AdvancedPaymentController.php and PaymentController.php.

I will check PaymentController.php for order confirmation or placing order.

I will read app/Http/Controllers/Api/PaymentController.php next.
</thinking>

<read_file>
<path>app/Http/Controllers/Api/PaymentController.php</path>
</read_file>
