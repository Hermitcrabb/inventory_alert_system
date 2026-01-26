<!DOCTYPE html>
<html>

<head>
    <title>Low Stock Alert</title>
</head>

<body>
    <h1>Low Stock Alert</h1>
    <p>The following product needs attention:</p>
    <ul>
        <li><strong>Product Title:</strong> {{ $productTitle }}</li>
        <li><strong>Variant:</strong> {{ $variantTitle }}</li>
        <li><strong>SKU:</strong> {{ $sku }}</li>
        <!-- <li><strong>Inventory Item ID:</strong> {{ $inventoryItemId }}</li> -->
        <li><strong>Available Quantity:</strong> {{ $currentInventory }}</li>
    </ul>
    <p>Please restock immediately.</p>
</body>

</html>