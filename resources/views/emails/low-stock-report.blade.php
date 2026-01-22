<!DOCTYPE html>
<html>

<head>
    <title>Low Stock Report</title>
    <style>
        body {
            font-family: sans-serif;
            line-height: 1.6;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }

        th {
            bg-color: #f4f4f4;
        }

        .low-stock {
            color: #d9534f;
            font-weight: bold;
        }

        .out-of-stock {
            color: #d9534f;
            font-weight: bold;
            text-transform: uppercase;
        }
    </style>
</head>

<body>
    <h2>Low Stock Report</h2>
    <p>The following items currently have 10 or fewer units in stock:</p>

    <table>
        <thead>
            <tr>
                <th>Product Name</th>
                <th>Product ID</th>
                <th>SKU</th>
                <th>Size</th>
                <th>Quantity</th>
                <th>Inventory Item ID</th>
            </tr>
        </thead>
        <tbody>
            @foreach($products as $product)
                <tr>
                    <td>{{ $product->title }}</td>
                    <td>{{ $product->shopify_product_id }}</td>
                    <td>{{ $product->sku }}</td>
                    <td>{{ $product->size ?? 'N/A' }}</td>
                    <td class="{{ $product->current_inventory <= 0 ? 'out-of-stock' : 'low-stock' }}">
                        {{ $product->current_inventory }}
                    </td>
                    <td>{{ $product->inventory_item_id }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p style="margin-top: 30px; font-size: 0.9em; color: #666;">
        Generated on {{ now()->format('M j, Y g:i A') }}
    </p>
</body>

</html>