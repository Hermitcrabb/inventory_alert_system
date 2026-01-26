<?php

namespace App\Services\Shopify;

use Illuminate\Support\Facades\Log;

class GraphQLService extends BaseShopifyService
{
    public function executeQuery(string $query, array $variables = []): array
    {
        $this->rateLimitCheck('graphql');

        $endpoint = 'admin/api/2024-01/graphql.json';

        $response = $this->makeRequest('post', $endpoint, [
            'query' => $query,
            'variables' => (object) $variables
        ]);

        // Pretty Log for debugging
        Log::info('GraphQL Process', [
            'query' => $query,
            'variables' => $variables,
            'response' => $response,
        ]);

        if (isset($response['errors'])) {
            Log::error('GraphQL errors detected', [
                'errors' => $response['errors'],
                'full_payload' => json_encode($response, JSON_PRETTY_PRINT),
                'shop' => $this->shopDomain,
            ]);
        }

        return $response;
    }

    /**
     * Find variant and product details by SKU
     */
    public function getVariantBySku(string $sku): array
    {
        $query = '
            query variantBySku($query: String!) {
              productVariants(first: 1, query: $query) {
                edges {
                  node {
                    id
                    sku
                    title
                    price
                    product {
                      id
                      title
                      handle
                      status
                      productType
                      vendor
                    }
                    inventoryItem {
                      id
                    }
                  }
                }
              }
            }
        ';

        $response = $this->executeQuery($query, [
            'query' => "sku:{$sku}"
        ]);

        return $response['data']['productVariants']['edges'][0]['node'] ?? [];
    }

    /**
     * Update inventory quantity for a specific inventory item at a location
     */
    public function inventorySet(string $inventoryItemId, string $locationId, int $available): array
    {
        $query = '
            mutation inventorySetQuantities($input: InventorySetQuantitiesInput!) {
              inventorySetQuantities(input: $input) {
                inventoryAdjustmentGroup {
                  id
                }
                userErrors {
                  field
                  message
                }
              }
            }
        ';

        $variables = [
            'input' => [
                'name' => 'available',
                'reason' => 'correction',
                'ignoreCompareQuantity' => true,
                'quantities' => [
                    [
                        'inventoryItemId' => $inventoryItemId,
                        'locationId' => $locationId,
                        'quantity' => $available
                    ]
                ]
            ]
        ];

        // Ensure IDs have gid prefix if they don't
        if (strpos($inventoryItemId, 'gid://') === false) {
            $variables['input']['quantities'][0]['inventoryItemId'] = "gid://shopify/InventoryItem/{$inventoryItemId}";
        } else {
            // Ensure it's the correct gid format if it's just a number string
            if (is_numeric($inventoryItemId)) {
                $variables['input']['quantities'][0]['inventoryItemId'] = "gid://shopify/InventoryItem/{$inventoryItemId}";
            }
        }

        if (strpos($locationId, 'gid://') === false) {
            $variables['input']['quantities'][0]['locationId'] = "gid://shopify/Location/{$locationId}";
        } else {
            if (is_numeric($locationId)) {
                $variables['input']['quantities'][0]['locationId'] = "gid://shopify/Location/{$locationId}";
            }
        }

        $response = $this->executeQuery($query, $variables);

        return $response['data']['inventorySetQuantities'] ?? [];
    }

    /**
     * Delete a product from Shopify
     */
    public function productDelete(string $productId): array
    {
        $query = '
            mutation productDelete($input: ProductDeleteInput!) {
              productDelete(input: $input) {
                deletedProductId
                userErrors {
                  field
                  message
                }
              }
            }
        ';

        // Ensure ID has gid prefix
        $gid = (strpos($productId, 'gid://') === false && is_numeric($productId))
            ? "gid://shopify/Product/{$productId}"
            : $productId;

        $variables = [
            'input' => [
                'id' => $gid
            ]
        ];

        $response = $this->executeQuery($query, $variables);

        return $response['data']['productDelete'] ?? [];
    }
}
