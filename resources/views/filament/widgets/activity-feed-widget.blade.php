<x-filament::widget>
    <x-filament::card>
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-medium">Recent Activity</h2>
            <div class="flex items-center space-x-2">
                <span class="text-sm text-gray-500">Auto-updates every 15s</span>
                <div class="animate-pulse h-2 w-2 rounded-full bg-green-500"></div>
            </div>
        </div>

        <div class="mt-4 flow-root">
            <ul role="list" class="-mb-8">
                @foreach ($activities as $activity)
                    <li>
                        <div class="relative pb-8">
                            @if (!$loop->last)
                                <span class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                            @endif
                            <div class="relative flex space-x-3">
                                <div>
                                    <span class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white bg-{{ $activity['color'] }}-100">
                                        <x-dynamic-component :component="$activity['icon']" class="h-5 w-5 text-{{ $activity['color'] }}-500" />
                                    </span>
                                </div>
                                <div class="flex min-w-0 flex-1 justify-between space-x-4">
                                    <div>
                                        <a href="{{ $activity['url'] }}" class="text-sm font-medium text-gray-900 hover:underline">
                                            {{ $activity['title'] }}
                                        </a>
                                        <p class="text-sm text-gray-500">{{ $activity['description'] }}</p>
                                    </div>
                                    <div class="whitespace-nowrap text-right text-sm text-gray-500">
                                        <time datetime="{{ $activity['timestamp'] }}">{{ $activity['timestamp']->diffForHumans() }}</time>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </x-filament::card>
</x-filament::widget>