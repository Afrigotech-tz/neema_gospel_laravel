<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NEEMA GOSPEL - Order Confirmation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background-color: #FF5600;
            color: #FFFFFF;
            padding: 30px;
            text-align: center;
        }
        .content {
            padding: 40px;
        }
        .order-details {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .order-details h3 {
            color: #FF5600;
            margin-top: 0;
        }
        .order-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 20px;
        }
        .order-info div {
            padding: 5px 0;
        }
        .order-info strong {
            color: #333;
        }
        .shipping-address {
            background-color: #f8f9fa;
            border-left: 4px solid #eb7e25;
            padding: 15px;
            margin: 20px 0;
        }
        .shipping-address h4 {
            color: #FF5600;
            margin-top: 0;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .items-table th {
            background-color: #FF5600;
            color: #FFFFFF;
            padding: 12px;
            text-align: left;
            font-weight: bold;
        }
        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }
        .items-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .items-table tr:hover {
            background-color: #f5f5f5;
        }
        .total-row {
            background-color: #f8f9fa;
            font-weight: bold;
            font-size: 16px;
        }
        .total-row td {
            border-top: 2px solid #FF5600;
            padding: 15px;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #6b7280;
        }
        .highlight {
            color: #f9ad7efd;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>NEEMA GOSPEL</h1>
            <p>Order Confirmation</p>
        </div>

        <div class="content">
            <h2>Hello {{ $order->user->name }}!</h2>
            <p>Thank you for your order! We're excited to let you know that we've received your order and it's being processed.</p>

            <div class="order-details">
                <h3>Order Details</h3>
                <div class="order-info">
                    <div><strong>Order Number:</strong> <span class="highlight">#{{ $order->order_number }}</span></div>
                    <div><strong>Order Date:</strong> {{ $order->created_at->format('M d, Y H:i A') }}</div>
                    <div><strong>Payment Method:</strong> {{ $order->paymentMethod->name ?? 'N/A' }}</div>
                    <div><strong>Order Status:</strong> <span class="highlight">{{ ucfirst($order->status) }}</span></div>
                </div>
            </div>

            <div class="shipping-address">
                <h4>Shipping Address</h4>
                <p>
                    {{ $order->address ? ($order->address->first_name . ' ' . $order->address->last_name) : $order->user->name }}<br>
                    {{ $order->address->address_line_1 ?? 'N/A' }}<br>
                    @if($order->address && $order->address->address_line_2)
                        {{ $order->address->address_line_2 }}<br>
                    @endif
                    {{ $order->address->city ?? 'N/A' }}, {{ $order->address->state ?? 'N/A' }} {{ $order->address->postal_code ?? 'N/A' }}<br>
                    {{ $order->address->country ?? 'N/A' }}
                </p>
            </div>

            <h3>Order Items</h3>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                        <tr>
                            <td>{{ $item->product_name }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>TZS {{ number_format($item->price, 2) }}</td>
                            <td>TZS {{ number_format($item->quantity * $item->price, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="3">Subtotal:</td>
                        <td>TZS {{ number_format($order->subtotal, 2) }}</td>
                    </tr>
                    @if($order->shipping_cost > 0)
                        <tr>
                            <td colspan="3">Shipping:</td>
                            <td>TZS {{ number_format($order->shipping_cost, 2) }}</td>
                        </tr>
                    @endif
                    @if($order->tax_amount > 0)
                        <tr>
                            <td colspan="3">Tax:</td>
                            <td>TZS {{ number_format($order->tax_amount, 2) }}</td>
                        </tr>
                    @endif
                    <tr class="total-row">
                        <td colspan="3">Total:</td>
                        <td>TZS {{ number_format($order->total_amount, 2) }}</td>
                    </tr>
                </tfoot>
            </table>

            <p>We'll send you another email when your order ships. If you have any questions about your order, please don't hesitate to contact us.</p>

            <p>Thank you for choosing NEEMA GOSPEL!</p>

            <p>Best regards,<br>
            The NEEMA GOSPEL Team</p>
        </div>

        <div class="footer">
            <p>This is an automated message. Please do not reply to this email.</p>
            <p>&copy; {{ date('Y') }} Neema Gospel. All rights reserved.</p>
        </div>
    </div>
</body>
</html>

