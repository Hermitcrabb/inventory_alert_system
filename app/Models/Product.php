<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'shopify_product_id',
        'shopify_variant_id',
        'title',
        'handle',
        'sku',
        'size',
        'current_inventory',
        'inventory_item_id',
        'product_type',
        'vendor',
        'status',
        'price',
        'compare_at_price',
        'last_synced_at',
    ];

    protected $casts = [
        'current_inventory' => 'integer',
        'price' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'last_synced_at' => 'datetime',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function thresholds(): HasMany
    {
        return $this->hasMany(InventoryThreshold::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(InventoryAlert::class);
    }

    public function scopeLowStock($query)
    {
        return $query->where('current_inventory', '<=', 20);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
