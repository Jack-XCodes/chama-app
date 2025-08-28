<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased">
    <div class="min-h-full">
        <!-- Mobile Navigation -->
        <nav x-data="{ open: false }" class="bg-white shadow-sm lg:hidden">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 justify-between">
                    <div class="flex">
                        <div class="flex flex-shrink-0 items-center">
                            <a href="{{ route('dashboard') }}">
                                <x-application-logo class="block h-8 w-auto" />
                            </a>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <button type="button" class="relative inline-flex items-center p-2 text-gray-600 hover:text-gray-900" @click="$dispatch('open-notifications')">
                            <x-heroicon-o-bell class="h-6 w-6" />
                            @if(auth()->user()->unread_notifications_count > 0)
                                <span class="absolute top-1 right-1 flex h-4 w-4">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-4 w-4 bg-red-500 text-xs text-white items-center justify-center">
                                        {{ auth()->user()->unread_notifications_count }}
                                    </span>
                                </span>
                            @endif
                        </button>
                        <button type="button" class="ml-2 inline-flex items-center justify-center rounded-md p-2 text-gray-600 hover:text-gray-900" @click="open = !open">
                            <span class="sr-only">Open main menu</span>
                            <x-heroicon-o-bars-3 x-show="!open" class="h-6 w-6" />
                            <x-heroicon-o-x-mark x-show="open" class="h-6 w-6" x-cloak />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile menu -->
            <div x-show="open" class="lg:hidden" x-cloak>
                <div class="space-y-1 pb-3 pt-2">
                    <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-responsive-nav-link>

                    @can('view-payments')
                        <x-responsive-nav-link :href="route('payments')" :active="request()->routeIs('payments')">
                            {{ __('Payments') }}
                        </x-responsive-nav-link>
                    @endcan

                    @can('view-documents')
                        <x-responsive-nav-link :href="route('documents')" :active="request()->routeIs('documents')">
                            {{ __('Documents') }}
                        </x-responsive-nav-link>
                    @endcan

                    @can('manage-finances')
                        <x-responsive-nav-link :href="route('treasurer.dashboard')" :active="request()->routeIs('treasurer.*')">
                            {{ __('Treasurer') }}
                        </x-responsive-nav-link>
                    @endcan

                    @can('manage-users')
                        <x-responsive-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.*')">
                            {{ __('Admin') }}
                        </x-responsive-nav-link>
                    @endcan
                </div>

                <!-- Mobile profile dropdown -->
                <div class="border-t border-gray-200 pb-3 pt-4">
                    <div class="flex items-center px-4">
                        <div class="flex-shrink-0">
                            <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                <span class="text-gray-600 font-medium text-sm">
                                    {{ substr(auth()->user()->name, 0, 2) }}
                                </span>
                            </div>
                        </div>
                        <div class="ml-3">
                            <div class="text-base font-medium text-gray-800">{{ auth()->user()->name }}</div>
                            <div class="text-sm font-medium text-gray-500">{{ auth()->user()->email }}</div>
                        </div>
                    </div>
                    <div class="mt-3 space-y-1">
                        <x-responsive-nav-link :href="route('profile')" :active="request()->routeIs('profile')">
                            {{ __('Profile') }}
                        </x-responsive-nav-link>

                        <x-responsive-nav-link :href="route('notification-preferences')" :active="request()->routeIs('notification-preferences')">
                            {{ __('Notifications') }}
                        </x-responsive-nav-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-responsive-nav-link :href="route('logout')"
                                onclick="event.preventDefault(); this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-responsive-nav-link>
                        </form>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Desktop Sidebar -->
        <div class="hidden lg:fixed lg:inset-y-0 lg:flex lg:w-64 lg:flex-col">
            @include('layouts.app.sidebar')
        </div>

        <!-- Main Content -->
        <div class="lg:pl-64">
            <main class="py-4">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    {{ $slot }}
                </div>
            </main>
        </div>

        <!-- Notifications Panel -->
        <div x-data="{ open: false }"
             x-show="open"
             x-on:open-notifications.window="open = true"
             x-on:close-notifications.window="open = false"
             x-on:keydown.escape.window="open = false"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform translate-x-full"
             x-transition:enter-end="opacity-100 transform translate-x-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 transform translate-x-0"
             x-transition:leave-end="opacity-0 transform translate-x-full"
             class="fixed inset-0 z-50 overflow-hidden"
             x-cloak>
            <div class="absolute inset-0 overflow-hidden">
                <div class="absolute inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                     x-show="open"
                     x-transition:enter="ease-in-out duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="ease-in-out duration-300"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     @click="open = false"></div>

                <div class="fixed inset-y-0 right-0 flex max-w-full pl-10 sm:pl-16">
                    <div class="w-screen max-w-md">
                        <livewire:notification-center />
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading Indicator -->
        <div x-data
             x-show="$store.loading.isLoading"
             x-transition.opacity
             class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
             x-cloak>
            <div class="animate-spin rounded-full h-12 w-12 border-4 border-brown-600 border-t-transparent"></div>
        </div>
    </div>

    @livewireScripts
    <script>
        // Loading state handling
        document.addEventListener('alpine:init', () => {
            Alpine.store('loading', {
                isLoading: false,
                show() { this.isLoading = true },
                hide() { this.isLoading = false }
            });
        });

        // Livewire loading indicators
        Livewire.on('loading', () => Alpine.store('loading').show());
        Livewire.on('loaded', () => Alpine.store('loading').hide());
    </script>
</body>
</html>