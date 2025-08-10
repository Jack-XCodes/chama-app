<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-brown-700 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
                <div class="rounded-xl border border-brown-100 bg-white p-6 shadow-sm">
                    <div class="text-sm font-medium text-brown-600">Welcome</div>
                    <div class="mt-2 text-2xl font-semibold text-brown-800">You're logged in!</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
