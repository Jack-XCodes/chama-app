<x-filament::widget>
    <x-filament::card>
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-medium">Quick Actions</h2>
        </div>

        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
            @foreach ($actions as $action)
                <div class="col-span-1">
                    {{ $action }}
                </div>
            @endforeach
        </div>
    </x-filament::card>
</x-filament::widget>