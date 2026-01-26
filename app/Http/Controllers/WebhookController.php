<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Product;
use App\Jobs\ProcessInventoryWebhook;

class WebhookController extends Controller
{
    /**
     * Handle incoming Shopify webhooks
     */
    public function handle(Request $request, $type)
    {
        Log::info('Webhook received', ['type' => $type, 'payload' => $request->all()]);

        if (!$this->verifyWebhook($request)) {
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $data = $request->all();

        switch ($type) {
            case 'inventory-update':
                ProcessInventoryWebhook::dispatch($data);
                break;

            case 'product-delete':
                if (isset($data['id'])) {
                    $productId = $data['id'];
                    $product = Product::where('product_id', $productId)->first();

                    if ($product) {
                        try {
                            $users = \App\Models\User::all();
                            foreach ($users as $user) {
                                \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\ProductDeletedAlert([
                                    'product_title' => $product->product_title,
                                    'sku' => $product->sku,
                                    'variant_title' => $product->variant_title,
                                    'quantity' => $product->quantity,
                                    'inventory_item_id' => $product->inventory_item_id,
                                ], 'Shopify Webhook'));

                                // Log Alert
                                \App\Models\AlertLog::log($product->product_id, $user->email, 'delete', $product->quantity);
                            }
                        } catch (\Exception $e) {
                            Log::error('Failed to send deletion email', ['error' => $e->getMessage()]);
                        }

                        $product->delete();
                    }
                }
                break;
        }

        return response()->json(['message' => 'Webhook received']);
    }

    /**
     * Verify Shopify webhook HMAC signature
     */
    private function verifyWebhook(Request $request): bool
    {
        $hmacHeader = $request->header('X-Shopify-Hmac-Sha256') ?: $request->header('X-Shopify-Hmac-SHA256');

        if (!$hmacHeader) {
            Log::warning('Webhook verification failed: Missing HMAC header');
            return false;
        }

        $data = $request->getContent();

        //webhook_secret and api_secret 
        $secrets = array_filter([
            config('services.shopify.webhook_secret'),
            config('services.shopify.api_secret'),
        ]);

        if (empty($secrets)) {
            Log::error('Webhook verification failed: No Shopify secrets configured');
            return false;
        }

        foreach ($secrets as $secret) {
            $calculatedHmac = base64_encode(hash_hmac('sha256', $data, $secret, true));

            if (hash_equals($calculatedHmac, $hmacHeader)) {
                Log::info('Webhook verified successfully', [
                    'secret_type' => ($secret === config('services.shopify.webhook_secret')) ? 'webhook_secret' : 'api_secret',
                ]);
                return true;
            }
        }

        Log::warning('Webhook verification failed: HMAC mismatch', [
            'header' => $hmacHeader,
            'topic' => $request->header('X-Shopify-Topic'),
        ]);

        return false;
    }
}