<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Products Report</title>
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

        .header {
            position: relative;
            padding: 10px 0;
            margin-bottom: 30px;
            height: 60px;
            border-bottom: 1px dotted #FF5600;
        }

        .logo {
            float: left;
            width: 60px;
            height: 60px;
            transform: translate(-50%, -50%);
        }

        .company-name {
            position: absolute;
            left: 50%;
            top: 30%;
            transform: translate(-50%, -50%);
            font-size: 24px;
            font-weight: bold;
            color: #FF5600;
            text-align: center;
            white-space: nowrap;
            padding-bottom: 30px;
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

        .status-active {
            color: #28a745;
            font-weight: bold;
        }

        .status-inactive {
            color: #dc3545;
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
        <div class="company-name">NEEMA GOSPEL CHOIR</div>
    </div>

    <h1>Products Report</h1>

    <!-- Summary -->
    <div class="summary">
        <p><strong>Generated:</strong> {{ $generated_at->format('Y-m-d H:i:s') }}</p>
        <p><strong>Total Products:</strong> {{ $summary['total_products'] }}</p>
        <p><strong>Active Products:</strong> {{ $summary['active_products'] }}</p>
        <p><strong>Inactive Products:</strong> {{ $summary['inactive_products'] }}</p>
        <p><strong>Total Stock:</strong> {{ $summary['total_stock'] }}</p>
        <p><strong>Category Filter:</strong> {{ $summary['category_filter'] }}</p>
        <p><strong>Status Filter:</strong> {{ $summary['status_filter'] }}</p>
    </div>

    <!-- Products Table -->
    <table>
        <thead>
            <tr>
                <th>S.No</th>
                <th>Product Name</th>
                <th>SKU</th>
                <th>Category</th>
                <th>Base Price</th>
                <th>Stock Qty</th>
                <th>Variants</th>
                <th>Status</th>
                <th>Created Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($products as $product)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $product->name }}</td>
                    <td>{{ $product->sku ?: 'N/A' }}</td>
                    <td>{{ $product->category->name ?? 'No Category' }}</td>
                    <td>${{ number_format($product->base_price, 2) }}</td>
                    <td>{{ $product->stock_quantity }}</td>
                    <td>{{ $product->variants->count() }}</td>
                    <td>
                        <span class="status-{{ $product->is_active ? 'active' : 'inactive' }}">
                            {{ $product->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td>{{ $product->created_at->format('Y-m-d H:i') }}</td>
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
