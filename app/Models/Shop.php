<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Services\Shopify\RestService;
use App\Services\Shopify\GraphQLService;

class Shop extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'shopify_domain',
        'access_token',
        'email',
        'country',
        'shop_owner',
        'plan_name',
        'currency',
        'status',
        'last_synced_at',
    ];

    protected $casts = [
        'access_token' => 'encrypted',
        'last_synced_at' => 'datetime',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function alertConfiguration()
    {
        return $this->hasOne(AlertConfiguration::class);
    }

    public function getRestService(): RestService
    {
        return app(RestService::class, [
            'shopDomain' => $this->shopify_domain,
            'accessToken' => $this->access_token
        ]);
    }

    public function getGraphQLService(): GraphQLService
    {
        return app(GraphQLService::class, [
            'shopDomain' => $this->shopify_domain,
            'accessToken' => $this->access_token
        ]);
    }
}
