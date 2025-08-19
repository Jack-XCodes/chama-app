<div class="flex h-full flex-col bg-white shadow-sm">
    <div class="flex flex-col flex-grow pt-5 pb-4 overflow-y-auto">
        <div class="flex items-center flex-shrink-0 px-4">
            <a href="{{ route('dashboard') }}" class="flex items-center">
                <x-application-logo class="block h-9 w-auto fill-current text-brown-600" />
                <span class="ml-3 text-xl font-semibold text-gray-900">{{ config('app.name') }}</span>
            </a>
        </div>
        <nav class="mt-8 flex-1 px-2 space-y-1">
            <!-- Dashboard -->
            <a href="{{ route('dashboard') }}" 
               class="{{ request()->routeIs('dashboard') ? 'bg-brown-50 text-brown-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }} group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                <svg class="mr-3 h-6 w-6 {{ request()->routeIs('dashboard') ? 'text-brown-600' : 'text-gray-400 group-hover:text-gray-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Dashboard
            </a>

            <!-- Payments -->
            @can('view-payments')
            <a href="{{ route('payments') }}" 
               class="{{ request()->routeIs('payments*') ? 'bg-brown-50 text-brown-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }} group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                <svg class="mr-3 h-6 w-6 {{ request()->routeIs('payments*') ? 'text-brown-600' : 'text-gray-400 group-hover:text-gray-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                Payments
            </a>
            @endcan

            <!-- Documents -->
            @can('view-documents')
            <a href="{{ route('documents') }}" 
               class="{{ request()->routeIs('documents*') ? 'bg-brown-50 text-brown-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }} group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                <svg class="mr-3 h-6 w-6 {{ request()->routeIs('documents*') ? 'text-brown-600' : 'text-gray-400 group-hover:text-gray-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
                Documents
            </a>
            @endcan

            <!-- Treasurer Section -->
            @can('manage-finances')
            <div class="space-y-1">
                <button type="button" 
                        class="text-gray-600 hover:bg-gray-50 hover:text-gray-900 group w-full flex items-center px-2 py-2 text-sm font-medium rounded-md"
                        @click="treasurerOpen = !treasurerOpen"
                        aria-expanded="false"
                        x-data="{ treasurerOpen: false }">
                    <svg class="mr-3 h-6 w-6 text-gray-400 group-hover:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Treasurer
                    <svg class="ml-auto h-5 w-5 transform text-gray-400 transition-transform duration-150" 
                         :class="{ 'rotate-90': treasurerOpen }"
                         viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                </button>
                <div class="space-y-1" x-show="treasurerOpen" x-cloak>
                    <a href="{{ route('treasurer.dashboard') }}" 
                       class="{{ request()->routeIs('treasurer.dashboard') ? 'bg-brown-50 text-brown-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }} group flex items-center pl-10 pr-2 py-2 text-sm font-medium rounded-md">
                        Dashboard
                    </a>
                    <a href="{{ route('treasurer.payments') }}" 
                       class="{{ request()->routeIs('treasurer.payments') ? 'bg-brown-50 text-brown-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }} group flex items-center pl-10 pr-2 py-2 text-sm font-medium rounded-md">
                        Payment Queue
                    </a>
                    <a href="{{ route('treasurer.manual-transaction') }}" 
                       class="{{ request()->routeIs('treasurer.manual-transaction') ? 'bg-brown-50 text-brown-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }} group flex items-center pl-10 pr-2 py-2 text-sm font-medium rounded-md">
                        Manual Transaction
                    </a>
                </div>
            </div>
            @endcan

            <!-- Reports -->
            @can('manage-finances')
            <div class="space-y-1">
                <button type="button" 
                        class="text-gray-600 hover:bg-gray-50 hover:text-gray-900 group w-full flex items-center px-2 py-2 text-sm font-medium rounded-md"
                        @click="reportsOpen = !reportsOpen"
                        aria-expanded="false"
                        x-data="{ reportsOpen: false }">
                    <svg class="mr-3 h-6 w-6 text-gray-400 group-hover:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Reports
                    <svg class="ml-auto h-5 w-5 transform text-gray-400 transition-transform duration-150" 
                         :class="{ 'rotate-90': reportsOpen }"
                         viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                </button>
                <div class="space-y-1" x-show="reportsOpen" x-cloak>
                    <a href="{{ route('reports.generate') }}" 
                       class="{{ request()->routeIs('reports.generate') ? 'bg-brown-50 text-brown-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }} group flex items-center pl-10 pr-2 py-2 text-sm font-medium rounded-md">
                        Generate Report
                    </a>
                    <a href="{{ route('reports.archive') }}" 
                       class="{{ request()->routeIs('reports.archive') ? 'bg-brown-50 text-brown-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }} group flex items-center pl-10 pr-2 py-2 text-sm font-medium rounded-md">
                        Report Archive
                    </a>
                </div>
            </div>
            @endcan

            <!-- Admin Section -->
            @can('manage-users')
            <div class="space-y-1">
                <button type="button" 
                        class="text-gray-600 hover:bg-gray-50 hover:text-gray-900 group w-full flex items-center px-2 py-2 text-sm font-medium rounded-md"
                        @click="adminOpen = !adminOpen"
                        aria-expanded="false"
                        x-data="{ adminOpen: false }">
                    <svg class="mr-3 h-6 w-6 text-gray-400 group-hover:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Admin
                    <svg class="ml-auto h-5 w-5 transform text-gray-400 transition-transform duration-150" 
                         :class="{ 'rotate-90': adminOpen }"
                         viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                </button>
                <div class="space-y-1" x-show="adminOpen" x-cloak>
                    <a href="{{ route('admin.dashboard') }}" 
                       class="{{ request()->routeIs('admin.dashboard') ? 'bg-brown-50 text-brown-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }} group flex items-center pl-10 pr-2 py-2 text-sm font-medium rounded-md">
                        Dashboard
                    </a>
                    <a href="{{ route('admin.announcements') }}" 
                       class="{{ request()->routeIs('admin.announcements') ? 'bg-brown-50 text-brown-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }} group flex items-center pl-10 pr-2 py-2 text-sm font-medium rounded-md">
                        Announcements
                    </a>
                </div>
            </div>
            @endcan
        </nav>
    </div>

    <!-- Profile Section -->
    <div class="flex-shrink-0 flex border-t border-gray-200 p-4">
        <div class="flex-shrink-0 w-full group block">
            <div class="flex items-center">
                <div>
                    <div class="h-9 w-9 rounded-full bg-gray-200 flex items-center justify-center text-gray-600">
                        {{ substr(auth()->user()->name, 0, 2) }}
                    </div>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-700 group-hover:text-gray-900">
                        {{ auth()->user()->name }}
                    </p>
                    <p class="text-xs font-medium text-gray-500 group-hover:text-gray-700">
                        View profile
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>