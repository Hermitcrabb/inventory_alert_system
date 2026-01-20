<?php

namespace App\Services\Shopify;

class GraphQLService extends BaseShopifyService
{
    public function executeQuery(string $query, array $variables = []): array
    {
        $this->rateLimitCheck('graphql');
        
        $endpoint = 'admin/api/2024-01/graphql.json';
        
        $response = $this->makeRequest('post', $endpoint, [
            'query' => $query,
            'variables' => $variables
        ]);
        
        if (isset($response['errors'])) {
            Log::error('GraphQL errors', [
                'errors' => $response['errors'],
                'shop' => $this->shopDomain,
            ]);
        }
        
        return $response;
    }
    
    public function getProductsWithInventory(string $cursor = null): array
    {
        $query = '
            query products($first: Int, $after: String) {
                products(first: $first, after: $after) {
                    pageInfo {
                        hasNextPage
                        endCursor
                    }
                    edges {
                        node {
                            id
                            title
                            handle
                            productType
                            vendor
                            status
                            tracksInventory
                            totalInventory
                            variants(first: 50) {
                                edges {
                                    node {
                                        id
                                        sku
                                        title
                                        price
                                        compareAtPrice
                                        inventoryQuantity
                                        inventoryPolicy
                                        inventoryItem {
                                            id
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        ';
        
        $response = $this->executeQuery($query, [
            'first' => 50,
            'after' => $cursor
        ]);
        
        return $response['data']['products'] ?? [];
    }
    
    public function getInventoryLevels(array $inventoryItemIds): array
    {
        $query = '
            query inventoryLevels($ids: [ID!]!) {
                nodes(ids: $ids) {
                    ... on InventoryItem {
                        id
                        inventoryLevels(first: 10) {
                            edges {
                                node {
                                    available
                                    location {
                                        id
                                        name
                                    }
                                }
                            }
                        }
                    }
                }
            }
        ';
        
        $response = $this->executeQuery($query, [
            'ids' => $inventoryItemIds
        ]);
        
        return $response['data']['nodes'] ?? [];
    }
    
    public function getProductById(string $productId): array
    {
        $query = '
            query product($id: ID!) {
                product(id: $id) {
                    id
                    title
                    variants(first: 10) {
                        edges {
                            node {
                                id
                                sku
                                inventoryQuantity
                                inventoryItem {
                                    id
                                }
                            }
                        }
                    }
                }
            }
        ';
        
        $response = $this->executeQuery($query, [
            'id' => $productId
        ]);
        
        return $response['data']['product'] ?? [];
    }
}