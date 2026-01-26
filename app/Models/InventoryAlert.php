<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'threshold_level',
        'new_inventory',
        'recipient_emails',
        'sent_at',
    ];

    protected $casts = [
        'threshold_level' => 'integer',
        'new_inventory' => 'integer',
        'sent_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
