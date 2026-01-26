<!DOCTYPE html>
<html>

<head>
    <title>Quantity Updated Alert</title>
</head>

<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 5px;">
        <h2 style="color: #1976d2;">Manual Quantity Update</h2>

        <p>The inventory quantity for the following product has been manually updated from the dashboard.</p>

        <div style="background-color: #f9f9f9; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <p><strong>Product Title:</strong> {{ $productTitle }}</p>
            <p><strong>SKU:</strong> {{ $sku }}</p>
            <p><strong>Variant:</strong> {{ $variantTitle }}</p>
            <p><strong>New Quantity:</strong> {{ $quantity }}</p>
            <!-- <p><strong>Inventory Item ID:</strong> {{ $inventoryItemId }}</p> -->
        </div>

        <p style="font-size: 0.9em; color: #666;">
            This email was sent automatically by your Inventory Alert System.
        </p>
    </div>
</body>

</html>