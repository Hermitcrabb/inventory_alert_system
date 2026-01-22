<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Inventory Dashboard') }}
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    Connected to: {{ config('services.shopify.store_domain') }}
                    @if(isset($lastSynced) && $lastSynced)
                        • Last sync: {{ $lastSynced->diffForHumans() }}
                    @endif
                </p>
            </div>

            <div class="flex space-x-2">
                <form action="{{ route('products.inform_admin') }}" method="POST">
                    @csrf
                    <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-amber-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-amber-700 focus:outline-none focus:border-amber-900 focus:ring ring-amber-300 transition ease-in-out duration-150"
                        title="Send low stock report to your email">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        Inform Admin
                    </button>
                </form>

                <form action="{{ route('products.sync') }}" method="POST">
                    @csrf
                    <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 transition ease-in-out duration-150"
                        title="Sync products from Shopify">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Sync Now
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Error Alert --}}
            @if(isset($error))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <div class="flex">
                        <div class="py-1">
                            <svg class="h-6 w-6 text-red-500 mr-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.928-.833-2.698 0L4.268 16.5c-.77.833.192 2.5 1.732 2.5z" />
                            </svg>
                        </div>
                        <div>
                            <p class="font-bold">Configuration Error</p>
                            <p class="text-sm">{{ $error }}</p>
                            <p class="text-xs mt-1">Please check your .env file for SHOPIFY_STORE_DOMAIN and
                                SHOPIFY_ADMIN_TOKEN</p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Success Messages --}}
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Stats Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Products</p>
                            <p class="text-2xl font-semibold text-gray-900">{{ $products->total() }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">In Stock</p>
                            <p class="text-2xl font-semibold text-gray-900">
                                {{ $products->total() - $lowStockCount - $outOfStockCount }}
                            </p>
                            <p class="text-xs text-gray-500">> 20 units</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Low Stock</p>
                            <p class="text-2xl font-semibold text-yellow-600">{{ $lowStockCount }}</p>
                            <p class="text-xs text-gray-500">≤ 20 units</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-red-500 rounded-md p-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.928-.833-2.698 0L4.268 16.5c-.77.833.192 2.5 1.732 2.5z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Out of Stock</p>
                            <p class="text-2xl font-semibold text-red-600">{{ $outOfStockCount }}</p>
                            <p class="text-xs text-gray-500">0 units</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Products Table --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Product Inventory</h3>

                        @if($products->total() > 0)
                            <div class="text-sm text-gray-600">
                                Showing {{ $products->firstItem() }} to {{ $products->lastItem() }} of
                                {{ $products->total() }} products
                            </div>
                        @endif
                    </div>

                    @if($products->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Product
                                        </th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            SKU / Variant
                                        </th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Inventory
                                        </th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Price
                                        </th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Type
                                        </th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Last Updated
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($products as $product)
                                                            <tr class="hover:bg-gray-50">
                                                                <td class="px-6 py-4">
                                                                    <div class="text-sm font-medium text-gray-900">
                                                                        {{ $product->title }}
                                                                    </div>
                                                                    <div class="text-xs text-gray-500 mt-1">
                                                                        Product ID: {{ $product->shopify_product_id }}
                                                                        @if($product->shopify_variant_id != $product->shopify_product_id)
                                                                            <br>Variant ID: {{ $product->shopify_variant_id }}
                                                                        @endif
                                                                    </div>
                                                                </td>
                                                                <td class="px-6 py-4">
                                                                    <div class="text-sm text-gray-900">
                                                                        {{ $product->sku ?? 'N/A' }}
                                                                    </div>
                                                                    @if($product->vendor)
                                                                        <div class="text-xs text-gray-500">
                                                                            {{ $product->vendor }}
                                                                        </div>
                                                                    @endif
                                                                </td>
                                                                <td class="px-6 py-4">
                                                                    <div class="text-2xl font-bold {{ 
                                                                        $product->current_inventory <= 0 ? 'text-red-600' :
                                        ($product->current_inventory <= 5 ? 'text-red-500' :
                                            ($product->current_inventory <= 10 ? 'text-yellow-600' :
                                                ($product->current_inventory <= 20 ? 'text-blue-600' : 'text-green-600'))) 
                                                                    }}">
                                                                        {{ $product->current_inventory }}
                                                                    </div>
                                                                    <div class="text-xs text-gray-500">
                                                                        units
                                                                    </div>
                                                                </td>
                                                                <td class="px-6 py-4">
                                                                    @if($product->current_inventory <= 0)
                                                                        <span
                                                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                                            Out of Stock
                                                                        </span>
                                                                    @elseif($product->current_inventory <= 5)
                                                                        <span
                                                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                                            Critical (≤5)
                                                                        </span>
                                                                    @elseif($product->current_inventory <= 10)
                                                                        <span
                                                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                                            Low (≤10)
                                                                        </span>
                                                                    @elseif($product->current_inventory <= 20)
                                                                        <span
                                                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                                            Warning (≤20)
                                                                        </span>
                                                                    @else
                                                                        <span
                                                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                                            In Stock
                                                                        </span>
                                                                    @endif
                                                                </td>
                                                                <td class="px-6 py-4">
                                                                    @if($product->price)
                                                                        <div class="text-sm font-medium text-gray-900">
                                                                            ${{ number_format($product->price, 2) }}
                                                                        </div>
                                                                        @if($product->compare_at_price)
                                                                            <div class="text-xs text-gray-500 line-through">
                                                                                ${{ number_format($product->compare_at_price, 2) }}
                                                                            </div>
                                                                        @endif
                                                                    @else
                                                                        <span class="text-sm text-gray-500">N/A</span>
                                                                    @endif
                                                                </td>
                                                                <td class="px-6 py-4">
                                                                    <div class="text-sm text-gray-900">
                                                                        {{ $product->product_type ?? 'N/A' }}
                                                                    </div>
                                                                </td>
                                                                <td class="px-6 py-4">
                                                                    <div class="text-sm text-gray-900">
                                                                        @if($product->last_synced_at)
                                                                            {{ $product->last_synced_at->format('M j, Y') }}
                                                                        @else
                                                                            Never
                                                                        @endif
                                                                    </div>
                                                                    <div class="text-xs text-gray-500">
                                                                        @if($product->last_synced_at)
                                                                            {{ $product->last_synced_at->format('g:i A') }}
                                                                        @endif
                                                                    </div>
                                                                </td>
                                                            </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Pagination --}}
                        <div class="mt-4">
                            {{ $products->links() }}
                        </div>

                    @else
                        <div class="text-center py-12">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-gray-400" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                    d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                            </svg>
                            <h3 class="mt-4 text-lg font-medium text-gray-900">No products found</h3>
                            <p class="mt-2 text-sm text-gray-600">
                                Products are being synced from {{ config('services.shopify.store_domain') }}
                            </p>
                            <p class="mt-1 text-sm text-gray-500">
                                This may take a few minutes. Click "Sync Now" to force refresh.
                            </p>
                            <div class="mt-6">
                                <form action="{{ route('products.sync') }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 transition ease-in-out duration-150">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                        Sync Products Now
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Configuration Info --}}
            <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="flex">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500 mt-0.5 mr-2" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <p class="text-sm text-blue-800">
                            <span class="font-semibold">Connected Store:</span>
                            {{ config('services.shopify.store_domain') }}
                        </p>
                        <p class="text-xs text-blue-600 mt-1">
                            Products are automatically synced from Shopify. Click "Sync Now" to refresh.
                            Inventory status updates automatically via webhooks.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>