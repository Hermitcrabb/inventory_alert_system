<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Low Stock Inventory') }}
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    Showing items with quantity &le; 20. Items above 20 are automatically removed.
                </p>
            </div>

            <div class="flex space-x-2">
                <form action="{{ route('products.sync') }}" method="POST">
                    @csrf
                    <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 transition ease-in-out duration-150"
                        title="Sync products from Shopify">
                        Sync Now
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Notifications --}}
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

            {{-- Summary Stats --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
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
                            <p class="text-sm font-medium text-gray-600">Low Stock Items</p>
                            <p class="text-2xl font-semibold text-yellow-600">{{ $lowStockCount }}</p>
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
                        </div>
                    </div>
                </div>
            </div>

            {{-- Low Stock Table --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Low Stock Table</h3>

                    @if($products->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product
                                            Title</th>

                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Variant
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($products as $product)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                                {{ $product->product_title }}
                                            </td>
                                            <!-- <td class="px-6 py-4 text-sm text-gray-500">{{ $product->product_id }}</td> -->
                                            <td class="px-6 py-4 text-sm text-gray-500">{{ $product->variant_title }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-500">{{ $product->sku }}</td>
                                            <td class="px-6 py-4">
                                                <form action="{{ route('products.update_quantity') }}" method="POST"
                                                    id="update-form-{{ $product->id }}" class="flex items-center space-x-2">
                                                    @csrf
                                                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                                                    <input type="number" name="quantity" value="{{ $product->quantity }}"
                                                        class="w-20 rounded-lg border-2 border-gray-300 shadow-inner focus:border-indigo-600 focus:ring-indigo-600 sm:text-sm font-bold text-gray-900 bg-gray-50"
                                                        min="0">
                                                </form>
                                            </td>
                                            <td class="px-6 py-4 text-sm flex items-center space-x-2">
                                                <button type="submit" form="update-form-{{ $product->id }}"
                                                    class="inline-flex items-center px-4 py-2 bg-indigo-700 text-black rounded-lg hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 text-xs font-bold uppercase tracking-wider shadow-md transition-all duration-200 transform active:scale-95">
                                                    Update
                                                </button>

                                                <!-- <form action="{{ route('products.delete') }}" method="POST"
                                                                    onsubmit="return confirm('Are you sure you want to delete this product from Shopify? This action cannot be undone.');">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <input type="hidden" name="id" value="{{ $product->id }}">
                                                                    <button type="submit"
                                                                        class="inline-flex items-center px-4 py-2 bg-red-600 text-black rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 text-xs font-bold uppercase tracking-wider shadow-md transition-all duration-200 transform active:scale-95">
                                                                        Delete
                                                                    </button>
                                                                </form> -->
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4">
                            {{ $products->links() }}
                        </div>
                    @else
                        <div class="text-center py-12">
                            <p class="text-gray-500">No low stock items currently tracked.</p>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>