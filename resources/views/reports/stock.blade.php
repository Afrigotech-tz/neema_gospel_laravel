<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Stock Report</title>
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

        .section-title {
            color: #FF5600;
            font-size: 16px;
            font-weight: bold;
            margin: 30px 0 15px 0;
            border-bottom: 2px solid #FF5600;
            padding-bottom: 5px;
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

        .stock-low {
            color: #ffc107;
            font-weight: bold;
        }

        .stock-out {
            color: #dc3545;
            font-weight: bold;
        }

        .stock-good {
            color: #28a745;
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

    <h1>Stock Report</h1>

    <!-- Summary -->
    <div class="summary">
        <p><strong>Generated:</strong> {{ $generated_at->format('Y-m-d H:i:s') }}</p>
        <p><strong>Filter Type:</strong> {{ $summary['filter_type'] }}</p>
        <p><strong>Total Products:</strong> {{ $summary['total_products'] }}</p>
        <p><strong>Total Variants:</strong> {{ $summary['total_variants'] }}</p>
        <p><strong>Low Stock Products (≤10):</strong> {{ $summary['low_stock_products'] }}</p>
        <p><strong>Out of Stock Products:</strong> {{ $summary['out_of_stock_products'] }}</p>
        <p><strong>Low Stock Variants (≤10):</strong> {{ $summary['low_stock_variants'] }}</p>
        <p><strong>Out of Stock Variants:</strong> {{ $summary['out_of_stock_variants'] }}</p>
        <p><strong>Total Product Stock:</strong> {{ $summary['total_product_stock'] }}</p>
        <p><strong>Total Variant Stock:</strong> {{ $summary['total_variant_stock'] }}</p>
    </div>

    @if($products->count() > 0)
    <!-- Products Stock Table -->
    <div class="section-title">Product Stock Levels</div>
    <table>
        <thead>
            <tr>
                <th>S.No</th>
                <th>Product Name</th>
                <th>SKU</th>
                <th>Category</th>
                <th>Stock Quantity</th>
                <th>Status</th>
                <th>Last Updated</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($products as $product)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $product->name }}</td>
                    <td>{{ $product->sku ?: 'N/A' }}</td>
                    <td>{{ $product->category->name ?? 'No Category' }}</td>
                    <td>
                        <span class="{{ $product->stock_quantity == 0 ? 'stock-out' : ($product->stock_quantity <= 10 ? 'stock-low' : 'stock-good') }}">
                            {{ $product->stock_quantity }}
                        </span>
                    </td>
                    <td>
                        @if($product->stock_quantity == 0)
                            <span class="stock-out">Out of Stock</span>
                        @elseif($product->stock_quantity <= 10)
                            <span class="stock-low">Low Stock</span>
                        @else
                            <span class="stock-good">In Stock</span>
                        @endif
                    </td>
                    <td>{{ $product->updated_at->format('Y-m-d H:i') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if($variants->count() > 0)
    <!-- Variants Stock Table -->
    <div class="section-title">Product Variants Stock Levels</div>
    <table>
        <thead>
            <tr>
                <th>S.No</th>
                <th>Product Name</th>
                <th>Variant SKU</th>
                <th>Category</th>
                <th>Stock Quantity</th>
                <th>Status</th>
                <th>Last Updated</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($variants as $variant)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $variant->product->name }}</td>
                    <td>{{ $variant->sku ?: 'N/A' }}</td>
                    <td>{{ $variant->product->category->name ?? 'No Category' }}</td>
                    <td>
                        <span class="{{ $variant->stock == 0 ? 'stock-out' : ($variant->stock <= 10 ? 'stock-low' : 'stock-good') }}">
                            {{ $variant->stock }}
                        </span>
                    </td>
                    <td>
                        @if($variant->stock == 0)
                            <span class="stock-out">Out of Stock</span>
                        @elseif($variant->stock <= 10)
                            <span class="stock-low">Low Stock</span>
                        @else
                            <span class="stock-good">In Stock</span>
                        @endif
                    </td>
                    <td>{{ $variant->updated_at->format('Y-m-d H:i') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if($products->count() == 0 && $variants->count() == 0)
    <div style="text-align: center; padding: 50px; color: #666;">
        <p>No stock data found for the selected filters.</p>
    </div>
    @endif

    <!-- Footer with page number -->
    <div class="footer">
        Page <span class="pagenum"></span><br> Generated at |
        {{ $generated_at->format('Y-m-d H:i:s') }}
    </div>
</body>

</html>
