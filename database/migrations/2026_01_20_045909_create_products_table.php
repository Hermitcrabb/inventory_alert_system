<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->onDelete('cascade');
            $table->bigInteger('shopify_product_id');
            $table->bigInteger('shopify_variant_id');
            $table->string('title');
            $table->string('handle')->nullable();
            $table->string('sku')->nullable();
            $table->integer('current_inventory')->default(0);
            $table->string('inventory_item_id')->nullable();
            $table->string('product_type')->nullable();
            $table->string('vendor')->nullable();
            $table->enum('status', ['active', 'archived', 'draft'])->default('active');
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('compare_at_price', 10, 2)->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            
            $table->index(['shop_id', 'shopify_product_id']);
            $table->index(['shop_id', 'current_inventory']);
            $table->index(['shop_id', 'last_synced_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
