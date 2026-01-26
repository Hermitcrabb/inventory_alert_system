<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlertLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'recipient_email',
        'type',
        'quantity',
    ];

    /**
     * Helper to log an alert
     * 
     * @param string|int|null $productId
     * @param string $email
     * @param string $type enum: 'low_stock', 'update', 'delete'
     * @param int $quantity
     */
    public static function log($productId, string $email, string $type, int $quantity)
    {
        self::create([
            'product_id' => $productId,
            'recipient_email' => $email,
            'type' => $type,
            'quantity' => $quantity,
        ]);
    }
}
