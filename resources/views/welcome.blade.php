<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="text-gray-900 flex p-6 lg:p-8 items-center lg:justify-center min-h-screen flex-col bg-gray-50">
    <header class="w-full lg:max-w-4xl max-w-md text-sm mb-6">
        @if (Route::has('login'))
            <nav class="flex items-center justify-end gap-4">
                @auth
                    <a href="{{ url('/dashboard') }}"
                        class="inline-block px-5 py-1.5 border border-gray-300 hover:border-gray-400 text-gray-900 rounded-md text-sm">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}"
                        class="inline-block px-5 py-1.5 text-gray-900 border border-transparent hover:border-gray-300 rounded-md text-sm">
                        Log in
                    </a>

                    @if (Route::has('register'))
                        <a href="{{ route('register') }}"
                            class="inline-block px-5 py-1.5 border border-gray-300 hover:border-gray-400 text-gray-900 rounded-md text-sm">
                            Register
                        </a>
                    @endif
                @endauth
            </nav>
        @endif
    </header>
    
    <main class="w-full lg:max-w-4xl max-w-md text-center">
        <h1 class="text-3xl lg:text-4xl font-semibold mb-4 text-gray-900">
            Inventory Low Stock Alert System
        </h1>
        <p class="text-base lg:text-lg text-gray-600 mb-6">
            A simple inventory alert system built with Laravel and Shopify integration.
        </p>
    </main>
</body>

</html>