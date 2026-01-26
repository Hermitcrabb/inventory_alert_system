<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('product_id')->default(0);
            $table->bigInteger('variant_id')->default(0);
            $table->string('inventory_item_id')->unique();
            $table->string('product_title')->default('');
            $table->string('variant_title')->default('');
            $table->string('sku'); // Mandatory as requested
            $table->integer('quantity')->default(0);
            $table->string('location_id')->default('');
            $table->integer('last_notified_threshold')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->index('inventory_item_id');
            $table->index('quantity');
            $table->index('sku');
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
