<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'variant_id',
        'inventory_item_id',
        'product_title',
        'variant_title',
        'sku',
        'quantity',
        'location_id',
        'last_notified_threshold',
        'last_notified_threshold_group',
        'last_synced_at',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'variant_id' => 'integer',
        'quantity' => 'integer',
        'last_notified_threshold' => 'integer',
        'last_notified_threshold_group' => 'integer',
        'last_synced_at' => 'datetime',
    ];

    public function alerts(): HasMany
    {
        return $this->hasMany(InventoryAlert::class);
    }

    public function scopeLowStock($query)
    {
        return $query->where('quantity', '<=', 20);
    }
}
