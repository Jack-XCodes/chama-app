<div class="space-y-6">
    <!-- Stats Overview -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
        <!-- Total Balance -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-brown-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-brown-500 truncate">Total Balance</dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-brown-900">
                                    KES {{ number_format($totalBalance, 2) }}
                                </div>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly Income -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-brown-500 truncate">Monthly Income</dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-green-600">
                                    KES {{ number_format($monthlyIncome, 2) }}
                                </div>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly Expenses -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-brown-500 truncate">Monthly Expenses</dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-red-600">
                                    KES {{ number_format($monthlyExpense, 2) }}
                                </div>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Payments Alert -->
    @if($pendingCount > 0)
        <div class="rounded-md bg-yellow-50 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800">
                        Attention needed
                    </h3>
                    <div class="mt-2 text-sm text-yellow-700">
                        <p>
                            There are {{ $pendingCount }} pending payments totaling KES {{ number_format($pendingAmount, 2) }} that require your review.
                        </p>
                    </div>
                    <div class="mt-4">
                        <div class="-mx-2 -my-1.5 flex">
                            <a href="/treasurer/payments" class="bg-yellow-100 px-2 py-1.5 rounded-md text-sm font-medium text-yellow-800 hover:bg-yellow-200">
                                View payments
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
        <!-- Transaction Chart -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-brown-900">Transaction History</h3>
                    <select
                        wire:model.live="timeframe"
                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-brown-300 focus:outline-none focus:ring-brown-500 focus:border-brown-500 sm:text-sm rounded-md"
                    >
                        <option value="week">Last Week</option>
                        <option value="month">Last Month</option>
                        <option value="quarter">Last Quarter</option>
                        <option value="year">Last Year</option>
                    </select>
                </div>
                <div class="relative">
                    <!-- Chart will be rendered here using Alpine.js and Chart.js -->
                    <div
                        x-data="{
                            chart: null,
                            init() {
                                this.chart = new Chart(this.$refs.canvas, {
                                    type: 'line',
                                    data: {
                                        labels: @js($chartData->pluck('date')),
                                        datasets: [
                                            {
                                                label: 'Income',
                                                data: @js($chartData->pluck('income')),
                                                borderColor: '#059669',
                                                backgroundColor: '#059669',
                                                fill: false,
                                            },
                                            {
                                                label: 'Expenses',
                                                data: @js($chartData->pluck('expense')),
                                                borderColor: '#DC2626',
                                                backgroundColor: '#DC2626',
                                                fill: false,
                                            }
                                        ]
                                    },
                                    options: {
                                        responsive: true,
                                        scales: {
                                            y: {
                                                beginAtZero: true,
                                                ticks: {
                                                    callback: function(value) {
                                                        return 'KES ' + value.toLocaleString();
                                                    }
                                                }
                                            }
                                        }
                                    }
                                });

                                this.$watch('$wire.chartData', (value) => {
                                    this.chart.data.labels = value.map(item => item.date);
                                    this.chart.data.datasets[0].data = value.map(item => item.income);
                                    this.chart.data.datasets[1].data = value.map(item => item.expense);
                                    this.chart.update();
                                });
                            }
                        }"
                    >
                        <canvas x-ref="canvas"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <h3 class="text-lg font-medium text-brown-900 mb-4">Recent Activity</h3>
                <div class="flow-root">
                    <ul role="list" class="-mb-8">
                        @foreach($recentActivity as $transaction)
                            <li>
                                <div class="relative pb-8">
                                    @if(!$loop->last)
                                        <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-brown-200" aria-hidden="true"></span>
                                    @endif
                                    <div class="relative flex space-x-3">
                                        <div>
                                            <span @class([
                                                'h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white',
                                                'bg-green-500' => $transaction->amount > 0,
                                                'bg-red-500' => $transaction->amount < 0,
                                            ])>
                                                @if($transaction->amount > 0)
                                                    <svg class="h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                                                    </svg>
                                                @else
                                                    <svg class="h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd" />
                                                    </svg>
                                                @endif
                                            </span>
                                        </div>
                                        <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                            <div>
                                                <p class="text-sm text-brown-500">
                                                    {{ $transaction->description }}
                                                    <span class="font-medium text-brown-900">
                                                        by {{ $transaction->user->name }}
                                                    </span>
                                                </p>
                                                @if($transaction->category)
                                                    <p class="text-sm text-brown-500">
                                                        Category: {{ $transaction->category->name }}
                                                    </p>
                                                @endif
                                            </div>
                                            <div class="text-right text-sm whitespace-nowrap">
                                                <time datetime="{{ $transaction->created_at->toIso8601String() }}">
                                                    {{ $transaction->created_at->diffForHumans() }}
                                                </time>
                                                <p @class([
                                                    'font-medium',
                                                    'text-green-600' => $transaction->amount > 0,
                                                    'text-red-600' => $transaction->amount < 0,
                                                ])>
                                                    {{ $transaction->formatted_amount }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
        <!-- Top Categories -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <h3 class="text-lg font-medium text-brown-900 mb-4">Top Categories</h3>
                <div class="space-y-4">
                    @foreach($categoryTotals as $category)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <span class="w-3 h-3 rounded-full" style="background-color: {{ $category->color }}"></span>
                                <span class="ml-2 text-sm font-medium text-brown-900">{{ $category->name }}</span>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-brown-900">
                                    KES {{ number_format($category->transactions_sum_amount, 2) }}
                                </p>
                                <p class="text-xs text-brown-500">
                                    {{ $category->transactions_count }} transactions
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Popular Tags -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <h3 class="text-lg font-medium text-brown-900 mb-4">Popular Tags</h3>
                <div class="flex flex-wrap gap-2">
                    @foreach($topTags as $tag)
                        <span class="inline-flex items-center px-3 py-0.5 rounded-full text-sm font-medium bg-{{ $tag->color }}-100 text-{{ $tag->color }}-800">
                            {{ $tag->name }}
                            <span class="ml-1 text-{{ $tag->color }}-600">
                                {{ $tag->transactions_count }}
                            </span>
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
