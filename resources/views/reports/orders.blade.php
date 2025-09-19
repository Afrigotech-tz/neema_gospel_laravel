<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Orders Report</title>
    <style>
        @page {
            margin: 20mm;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 0;
            padding: 0;
        }

        /* Header with logo on the left */
        .header {
            display: flex;
            align-items: center;
            border-bottom: 2px solid #FF5600;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }

        .logo {
            width: 60px;
            height: 60px;
            margin-right: 15px;
            display: block;
        }

        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #FF5600;
        }

        h1 {
            color: #FF5600;
            font-size: 20px;
            margin: 20px 0;
            text-align: center;
        }

        .summary {
            background-color: #f8f9fa;
            padding: 15px;
            margin: 20px 0;
            border-left: 4px solid #FF5600;
            border-radius: 4px;
        }

        .summary p {
            margin: 5px 0;
            font-size: 12px;
        }

        .summary strong {
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        th {
            background-color: #FF5600;
            color: white;
            padding: 12px 8px;
            text-align: left;
            font-weight: bold;
            font-size: 11px;
        }

        td {
            padding: 10px 8px;
            border-bottom: 1px solid #ddd;
            font-size: 11px;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .amount {
            text-align: right;
            font-weight: bold;
        }

        /* Footer with page number */
        .footer {
            position: fixed;
            bottom: 10px;
            width: 100%;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 5px;
        }

        /* For PDF generators like dompdf */
        .pagenum:before {
            content: counter(page);
        }
    </style>
</head>

<body>
    <!-- Header -->
    <div class="header">
        <img src="{{ $logoPath }}" alt="Logo" class="logo">
        <div class="company-name">NEEMA GOSPEL</div>
    </div>

    <h1>Orders Report</h1>

    <!-- Summary -->
    <div class="summary">
        <p><strong>Generated:</strong> {{ $generated_at->format('Y-m-d H:i:s') }}</p>
        <p><strong>Date Range:</strong>
            @if ($summary['date_range']['start'])
                {{ $summary['date_range']['start'] }}
            @else
                All time
            @endif
            to
            @if ($summary['date_range']['end'])
                {{ $summary['date_range']['end'] }}
            @else
                Present
            @endif
        </p>
        <p><strong>Status Filter:</strong> {{ $summary['status_filter'] }}</p>
        <p><strong>Total Orders:</strong> {{ $summary['total_orders'] }}</p>
        <p><strong>Total Amount:</strong> {{ number_format($summary['total_amount'], 2) }} TZS </p>
    </div>

    <!-- Orders Table -->
    <table>
        <thead>
            <tr>
                <th>Order ID#</th>
                <th>Customer</th>
                <th>Status</th>
                <th>Payment</th>
                <th>Amount</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($orders as $order)
                <tr>
                    <td>{{ $order->order_number }}</td>
                    <td>{{ $order->user->name ?? 'N/A' }}</td>
                    <td>{{ ucfirst($order->status) }}</td>
                    <td>{{ ucfirst($order->payment_status) }}</td>
                    <td class="amount"> {{ number_format($order->total_amount, 2) }} TZS</td>
                    <td>{{ $order->created_at->format('Y-m-d H:i') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Footer with page number -->
    <div class="footer">
        Page <span class="pagenum"></span><br> Generated at |
         {{ $generated_at->format('Y-m-d H:i:s') }}
    </div>
</body>

</html>
