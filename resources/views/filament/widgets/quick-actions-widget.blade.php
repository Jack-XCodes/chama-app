<x-filament-widgets::widget>
    <div class="p-2 space-y-2">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
            @foreach ($this->getActions() as $action)
                {{ $action }}
            @endforeach
        </div>
    </div>
</x-filament-widgets::widget>