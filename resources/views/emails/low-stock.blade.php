<!DOCTYPE html>
<html>

<head>
    <title>Low Stock Alert</title>
</head>

<body>
    <h1>Low Stock Alert</h1>
    <p>The following product has dropped below the threshold:</p>
    <ul>
        <li><strong>Product:</strong> {{ $product->title }}</li>
        <li><strong>Current Inventory:</strong> {{ $currentInventory }}</li>
        <li><strong>Threshold:</strong> {{ $threshold }}</li>
    </ul>
    <p>Please restock immediately.</p>
</body>

</html>