<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Webhook Status
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-4">Webhook Configuration</h3>
                
                <div class="mb-6">
                    <p><strong>Webhook URL:</strong> {{ $webhook_url }}</p>
                    <p><strong>ngrok URL:</strong> {{ $ngrok_url }}</p>
                    <p class="text-sm text-gray-600 mt-2">
                        This URL should be set in Shopify Admin → Settings → Notifications → Webhooks
                    </p>
                </div>
                
                <div class="mb-6">
                    <h4 class="font-medium mb-2">To Test:</h4>
                    <ol class="list-decimal pl-5 text-sm space-y-1">
                        <li>Go to Shopify Admin → Products</li>
                        <li>Edit any product's inventory</li>
                        <li>Save changes</li>
                        <li>Check logs below for webhook receipt</li>
                    </ol>
                </div>
                
                <div class="mb-6">
                    <h4 class="font-medium mb-2">Recent Logs</h4>
                    <div class="bg-gray-900 text-green-400 p-4 rounded font-mono text-xs overflow-auto max-h-96">
                        <pre>{{ $log_snippet }}</pre>
                    </div>
                </div>
                
                <div class="mb-6">
                    <h4 class="font-medium mb-2">Alerts Triggered</h4>
                    <div class="bg-gray-900 text-yellow-400 p-4 rounded font-mono text-xs overflow-auto max-h-48">
                        <pre>{{ $alert_snippet }}</pre>
                    </div>
                </div>
                
                <div class="flex space-x-4">
                    <a href="{{ route('register.webhooks') }}" 
                       class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        Register Webhooks
                    </a>
                    <a href="{{ route('list.webhooks') }}" target="_blank"
                       class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                        List Webhooks
                    </a>
                    <a href="{{ route('webhooks.test') }}"
                       class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                        Test Webhook
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>