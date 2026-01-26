<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Alert History') }}
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    Historical record of low stock notifications for {{ $shop->shopify_domain }}
                </p>
            </div>
            <div>
                <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 transition ease-in-out duration-150">
                    Back to Dashboard
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if($alerts->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Threshold</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Inventory Level</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Recipients</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sent At</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($alerts as $alert)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4">
                                                <div class="text-sm font-medium text-gray-900">{{ $alert->product->title ?? 'Unknown Product' }}</div>
                                                <div class="text-xs text-gray-500">SKU: {{ $alert->product->sku ?? 'N/A' }}</div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                    ≤ {{ $alert->threshold_level }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="flex items-center space-x-2">
                                                    <span class="text-lg font-bold {{ $alert->new_inventory <= 5 ? 'text-red-600' : ($alert->new_inventory <= 10 ? 'text-yellow-600' : 'text-blue-600') }}">
                                                        {{ $alert->new_inventory }}
                                                    </span>
                                                </div>
                                                <div class="text-[10px] text-gray-400 uppercase tracking-tighter mt-1">
                                                    @if($alert->new_inventory <= 0)
                                                        Out of Stock
                                                    @elseif($alert->new_inventory <= 5)
                                                        Critical Low Stock
                                                    @else
                                                        Low Stock Alert
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-xs text-gray-600 max-w-xs truncate" title="{{ $alert->recipient_emails }}">
                                                    {{ $alert->recipient_emails ?: 'Admin Email' }}
                                                </div>
                                                @if(!$alert->recipient_emails)
                                                    <div class="text-[10px] text-gray-400 italic">Sent to default configured email</div>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $alert->sent_at ? $alert->sent_at->format('M j, Y g:i A') : $alert->created_at->format('M j, Y g:i A') }}
                                                <div class="text-xs text-gray-400 italic">
                                                    {{ ($alert->sent_at ?: $alert->created_at)->diffForHumans() }}
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4">
                            {{ $alerts->links() }}
                        </div>
                    @else
                        <div class="text-center py-12">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <h3 class="text-lg font-medium text-gray-900">No alert history found</h3>
                            <p class="text-sm text-gray-500 mt-1">Alerts will appear here as webhooks trigger low stock notifications.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
