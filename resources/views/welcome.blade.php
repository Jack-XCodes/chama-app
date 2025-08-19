<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Chama App') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brown: {
                            50: '#fdf8f6',
                            100: '#f2e8e5',
                            200: '#eaddd7',
                            300: '#e0cec7',
                            400: '#d2bab0',
                            500: '#a18072',
                            600: '#8B4513',
                            700: '#713A0F',
                            800: '#572D0D',
                            900: '#2C170A',
                        },
                    },
                },
            },
        }
    </script>
    <style>
        [x-cloak] { display: none !important; }
        .animate-float {
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
    </style>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="antialiased bg-gradient-to-br from-gray-50 to-gray-100">
    <div class="relative min-h-screen" x-data="{ mobileMenuOpen: false }">
        <!-- Navigation -->
        <nav class="bg-white/80 backdrop-blur-md shadow-sm">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 justify-between items-center">
                    <div class="flex-shrink-0">
                        <a href="{{ url('/') }}" class="flex items-center">
                            <img src="{{ asset('favicon.svg') }}" class="h-8 w-auto" alt="Logo">
                            <span class="ml-3 text-xl font-semibold text-gray-900">{{ config('app.name') }}</span>
                        </a>
                    </div>

                    <!-- Desktop Navigation -->
                    <div class="hidden md:flex md:items-center md:space-x-6">
                        @auth
                            <a href="{{ route('dashboard') }}" class="text-base font-medium text-gray-900 hover:text-brown-600 transition-colors duration-300">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="text-base font-medium text-gray-900 hover:text-brown-600 transition-colors duration-300">
                                Sign in
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-md bg-gradient-to-r from-brown-600 to-brown-700 px-4 py-2 text-base font-medium text-white shadow-lg hover:from-brown-700 hover:to-brown-800 transition-all duration-300">
                                    Get started
                                </a>
                            @endif
                        @endauth
                    </div>

                    <!-- Mobile menu button -->
                    <div class="flex md:hidden">
                        <button @click="mobileMenuOpen = !mobileMenuOpen" type="button" class="text-gray-500 hover:text-gray-600">
                            <span class="sr-only">Open menu</span>
                            <svg class="h-6 w-6" x-show="!mobileMenuOpen" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                            </svg>
                            <svg class="h-6 w-6" x-show="mobileMenuOpen" x-cloak fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile menu -->
            <div x-show="mobileMenuOpen" class="md:hidden" x-cloak>
                <div class="space-y-1 px-4 pb-3 pt-2">
                    @auth
                        <a href="{{ route('dashboard') }}" class="block py-2 text-base font-medium text-gray-900 hover:bg-gray-50 rounded-md">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="block py-2 text-base font-medium text-gray-900 hover:bg-gray-50 rounded-md">
                            Sign in
                        </a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="block py-2 text-base font-medium text-gray-900 hover:bg-gray-50 rounded-md">
                                Get started
                            </a>
                        @endif
                    @endauth
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <div class="relative isolate px-6 pt-14 lg:px-8">
            <div class="mx-auto max-w-2xl py-32 sm:py-48 lg:py-56">
                <div class="text-center">
                    <h1 class="text-4xl font-bold tracking-tight text-gray-900 sm:text-6xl">
                        Manage Your Chama Group Efficiently
                    </h1>
                    <p class="mt-6 text-lg leading-8 text-gray-600">
                        Streamline your investment group's operations with our comprehensive management platform. Handle payments, documents, and communications all in one place.
                    </p>
                    <div class="mt-10 flex items-center justify-center gap-x-6">
                        @auth
                            <a href="{{ route('dashboard') }}" class="rounded-md bg-gradient-to-r from-brown-600 to-brown-700 px-3.5 py-2.5 text-sm font-semibold text-white shadow-lg hover:from-brown-700 hover:to-brown-800 transition-all duration-300">
                                Go to Dashboard
                            </a>
                        @else
                            <a href="{{ route('register') }}" class="rounded-md bg-gradient-to-r from-brown-600 to-brown-700 px-3.5 py-2.5 text-sm font-semibold text-white shadow-lg hover:from-brown-700 hover:to-brown-800 transition-all duration-300">
                                Get started
                            </a>
                            <a href="{{ route('login') }}" class="text-sm font-semibold leading-6 text-gray-900 hover:text-brown-600 transition-colors duration-300">
                                Sign in <span aria-hidden="true">→</span>
                            </a>
                        @endauth
                    </div>
                </div>
            </div>
        </div>

        <!-- Features Section -->
        <div class="bg-white py-24 sm:py-32">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <h2 class="text-base font-semibold leading-7 text-brown-600">Everything you need</h2>
                    <p class="mt-2 text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">Powerful Features for Your Group</p>
                </div>

                <div class="mx-auto mt-16 max-w-2xl sm:mt-20 lg:mt-24 lg:max-w-none">
                    <dl class="grid max-w-xl grid-cols-1 gap-x-8 gap-y-16 lg:max-w-none lg:grid-cols-3">
                        <!-- Payment Management -->
                        <div class="flex flex-col">
                            <dt class="flex items-center gap-x-3 text-base font-semibold leading-7 text-gray-900">
                                <svg class="h-5 w-5 flex-none text-brown-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                                </svg>
                                Payment Management
                            </dt>
                            <dd class="mt-4 flex flex-auto flex-col text-base leading-7 text-gray-600">
                                <p class="flex-auto">Easy payment submissions, automated reconciliation, and comprehensive transaction tracking for all group finances.</p>
                            </dd>
                        </div>

                        <!-- Document Management -->
                        <div class="flex flex-col">
                            <dt class="flex items-center gap-x-3 text-base font-semibold leading-7 text-gray-900">
                                <svg class="h-5 w-5 flex-none text-brown-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                </svg>
                                Document Management
                            </dt>
                            <dd class="mt-4 flex flex-auto flex-col text-base leading-7 text-gray-600">
                                <p class="flex-auto">Secure storage and organization of group documents with version control and easy sharing capabilities.</p>
                            </dd>
                        </div>

                        <!-- Financial Reports -->
                        <div class="flex flex-col">
                            <dt class="flex items-center gap-x-3 text-base font-semibold leading-7 text-gray-900">
                                <svg class="h-5 w-5 flex-none text-brown-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 14.25v2.25m3-4.5v4.5m3-6.75v6.75m3-9v9M6 20.25h12A2.25 2.25 0 0020.25 18V6A2.25 2.25 0 0018 3.75H6A2.25 2.25 0 003.75 6v12A2.25 2.25 0 006 20.25z" />
                                </svg>
                                Financial Reports
                            </dt>
                            <dd class="mt-4 flex flex-auto flex-col text-base leading-7 text-gray-600">
                                <p class="flex-auto">Automated generation of professional financial reports including balance sheets, P&L statements, and member contributions.</p>
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        <!-- CTA Section -->
        <div class="bg-gradient-to-r from-brown-600 to-brown-700">
            <div class="mx-auto max-w-7xl px-6 py-24 sm:py-32 lg:px-8">
                <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">Ready to streamline your group's operations?</h2>
                <p class="mt-6 text-lg leading-8 text-brown-100">Join thousands of successful investment groups using our platform to manage their operations efficiently.</p>
                <div class="mt-10 flex items-center gap-x-6">
                    @auth
                        <a href="{{ route('dashboard') }}" class="rounded-md bg-white px-3.5 py-2.5 text-sm font-semibold text-brown-600 shadow-sm hover:bg-brown-50 transition-all duration-300">
                            Go to Dashboard
                        </a>
                    @else
                        <a href="{{ route('register') }}" class="rounded-md bg-white px-3.5 py-2.5 text-sm font-semibold text-brown-600 shadow-sm hover:bg-brown-50 transition-all duration-300">
                            Start for free
                        </a>
                        <a href="#features" class="text-sm font-semibold leading-6 text-white hover:text-brown-100 transition-colors duration-300">
                            Learn more <span aria-hidden="true">→</span>
                        </a>
                    @endauth
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="bg-white">
            <div class="mx-auto max-w-7xl px-6 py-12 md:flex md:items-center md:justify-between lg:px-8">
                <div class="flex justify-center space-x-6 md:order-2">
                    <a href="#" class="text-gray-400 hover:text-brown-600 transition-colors duration-300">
                        <span class="sr-only">Twitter</span>
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24"><path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84" /></svg>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-brown-600 transition-colors duration-300">
                        <span class="sr-only">GitHub</span>
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" /></svg>
                    </a>
                </div>
                <div class="mt-8 md:order-1 md:mt-0">
                    <p class="text-center text-xs leading-5 text-gray-500">&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
                </div>
            </div>
        </footer>
    </div>
</body>
</html>