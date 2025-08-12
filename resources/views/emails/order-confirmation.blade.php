<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .order-details {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .item {
            border-bottom: 1px solid #eee;
            padding: 10px 0;
        }
        .item:last-child {
            border-bottom: none;
        }
        .total {
            font-weight: bold;
            font-size: 1.2em;
            text-align: right;
            margin-top: 10px;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 0.9em;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Order Confirmation</h1>
        <p>Thank you for your order!</p>
    </div>

    <div class="order-details">
        <h2>Order Details</h2>
        <p><strong>Order Number:</strong> {{ $order->order_number }}</p>
        <p><strong>Order Date:</strong> {{ $order->created_at->format('M d, Y H:i') }}</p>
        <p><strong>Status:</strong> {{ ucfirst($order->status) }}</p>
    </div>

    <div class="order-details">
        <h3>Shipping Address</h3>
        <p>
            {{ $order->address->full_name }}<br>
            {{ $order->address->street_address }}<br>
            {{ $order->address->city }}, {{ $order->address->state }} {{ $order->address->postal_code }}<br>
            {{ $order->address->country }}
        </p>
    </div>

    <div class="order-details">
        <h3>Order Items</h3>
        @foreach($order->items as $item)
            <div class="item">
                <strong>{{ $item->product_name }}</strong><br>
                Quantity: {{ $item->quantity }}<br>
                Price: ${{ number_format($item->price, 2) }}<br>
                Total: ${{ number_format($item->total, 2) }}
            </div>
        @endforeach

        <div class="total">
            Total: ${{ number_format($order->total_amount, 2) }}
        </div>
    </div>

    <div class="footer">
        <p>If you have any questions about your order, please contact our customer service team.</p>
        <p>Thank you for shopping with us!</p>
    </div>
</body>
</html>
