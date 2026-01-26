<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Product;
use App\Services\Shopify\RestService;
use App\Services\Shopify\GraphQLService;

class ProductsController extends Controller
{
    public function index(Request $request)
    {
        // Credentials check
        $storeDomain = config('services.shopify.store_domain');
        $adminToken = config('services.shopify.admin_token');

        if (!$storeDomain || !$adminToken) {
            return view('dashboard', [
                'products' => collect(),
                'lowStockCount' => 0,
                'outOfStockCount' => 0,
                'error' => 'Shopify credentials not configured in .env file'
            ]);
        }

        // Only show products in local DB (which are all <= 20)
        $products = Product::where('quantity', '<=', 20)
            ->orderBy('quantity', 'asc')
            ->orderBy('product_title', 'asc')
            ->paginate(50);

        $lowStockCount = Product::where('quantity', '>', 0)
            ->where('quantity', '<=', 20)
            ->count();

        $outOfStockCount = Product::where('quantity', '<=', 0)
            ->count();

        return view('dashboard', [
            'products' => $products,
            'lowStockCount' => $lowStockCount,
            'outOfStockCount' => $outOfStockCount,
            'lastSynced' => Product::max('last_synced_at')
        ]);
    }

    /**
     * Update product quantity in local DB and Shopify
     */
    public function updateQuantity(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:0'
        ]);

        $product = Product::findOrFail($request->input('product_id'));
        $newQuantity = (int) $request->input('quantity');

        Log::info('Start Quantity Update Process', [
            'product_id' => $product->id,
            'sku' => $product->sku,
            'new_quantity' => $newQuantity,
            'location_id' => $product->location_id,
        ]);

        try {
            // 1. Update Shopify via GraphQL
            if (empty($product->location_id)) {
                Log::info('Location ID missing, fetching from Shopify...');
                $restService = new RestService();
                $locations = $restService->getLocations();
                $locationId = $locations[0]['id'] ?? null;
                if ($locationId) {
                    $product->update(['location_id' => $locationId]);
                    Log::info('Location ID updated', ['location_id' => $locationId]);
                } else {
                    throw new \Exception('No Shopify location found for this store.');
                }
            }

            $graphQLService = new GraphQLService();
            $result = $graphQLService->inventorySet(
                $product->inventory_item_id,
                $product->location_id,
                $newQuantity
            );

            Log::info('GraphQL Sync Result', ['result' => $result]);

            if (!empty($result['userErrors'])) {
                $error = $result['userErrors'][0]['message'];
                Log::error('Shopify Sync UserError', ['error' => $error, 'field' => $result['userErrors'][0]['field']]);
                return redirect()->back()->with('error', 'Shopify Sync Error: ' . $error);
            }

            // 2. Handle local DB
            if ($newQuantity > 20) {
                $product->delete();
                Log::info('Product removed from local DB (quantity > 20)');
                $message = 'Quantity updated to ' . $newQuantity . '. Product removed from low stock list.';
            } else {
                $product->update([
                    'quantity' => $newQuantity,
                    'last_synced_at' => now()
                ]);
                Log::info('Local DB updated', ['new_quantity' => $newQuantity]);
                $message = 'Quantity updated successfully to ' . $newQuantity . '.';

                // 2b. Send Update Email
                try {
                    $users = \App\Models\User::all();
                    foreach ($users as $user) {
                        \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\QuantityUpdatedAlert([
                            'product_title' => $product->product_title,
                            'sku' => $product->sku,
                            'variant_title' => $product->variant_title,
                            'quantity' => $newQuantity,
                            'inventory_item_id' => $product->inventory_item_id,
                        ]));

                        // Log Alert
                        \App\Models\AlertLog::log($product->product_id, $user->email, 'update', $newQuantity);
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to send update email', ['error' => $e->getMessage()]);
                }
            }

            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Quantity update failed', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()->with('error', 'Update failed: ' . $e->getMessage());
        }
    }


    /**
     * Delete product from local DB and Shopify
     */
    public function deleteProduct(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:products,id',
        ]);

        $product = Product::findOrFail($request->input('id'));
        $shopifyProductId = (string) $product->product_id;

        Log::info('Start Product Deletion Process', [
            'db_id' => $product->id,
            'shopify_product_id' => $shopifyProductId,
            'sku' => $product->sku,
        ]);

        try {
            // 1. Delete from Shopify via GraphQL
            $graphQLService = new GraphQLService();
            $result = $graphQLService->productDelete($shopifyProductId);

            Log::info('GraphQL Delete Result', ['result' => $result]);

            if (!empty($result['userErrors'])) {
                $error = $result['userErrors'][0]['message'];
                return redirect()->back()->with('error', 'Shopify Delete Error: ' . $error);
            }

            // 2. Send Deletion Alert
            try {
                $users = \App\Models\User::all();
                foreach ($users as $user) {
                    \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\ProductDeletedAlert([
                        'product_title' => $product->product_title,
                        'sku' => $product->sku,
                        'variant_title' => $product->variant_title,
                        'quantity' => $product->quantity,
                        'inventory_item_id' => $product->inventory_item_id,
                    ], 'Dashboard Manual Action'));

                    // Log Alert
                    \App\Models\AlertLog::log($product->product_id, $user->email, 'delete', $product->quantity);
                }
            } catch (\Exception $e) {
                Log::error('Failed to send deletion email', ['error' => $e->getMessage()]);
            }

            // 3. Delete from local DB
            $product->delete();
            Log::info('Product deleted from local DB');

            return redirect()->back()->with('success', 'Product deleted successfully from Shopify and local dashboard.');

        } catch (\Exception $e) {
            Log::error('Product deletion failed', [
                'db_id' => $product->id,
                'error' => $e->getMessage(),
            ]);
            return redirect()->back()->with('error', 'Deletion failed: ' . $e->getMessage());
        }
    }


    public function manualSync(Request $request)
    {
        \App\Jobs\SyncShopifyProducts::dispatch();
        return redirect()->route('dashboard')->with('success', 'Product sync initiated.');
    }
}
