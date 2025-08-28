<x-app-layout>
    <div class="py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-semibold text-gray-900">Welcome back, {{ Auth::user()->name }}!</h1>
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <button @click="$refs.notificationPanel.classList.toggle('hidden')" class="relative p-2 text-gray-600 hover:text-gray-900">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            @if(count($notifications) > 0)
                                <span class="absolute top-0 right-0 block h-2 w-2 rounded-full bg-red-400 ring-2 ring-white"></span>
                            @endif
                        </button>
                        <div x-ref="notificationPanel" class="absolute right-0 mt-2 hidden w-80 rounded-lg bg-white shadow-lg ring-1 ring-black ring-opacity-5">
                            <div class="p-4">
                                <h3 class="text-sm font-medium text-gray-900">Notifications</h3>
                                @forelse($notifications as $notification)
                                    <div class="mt-2 border-t border-gray-100 pt-2">
                                        <p class="text-sm font-medium text-gray-900">{{ $notification['title'] }}</p>
                                        <p class="mt-1 text-sm text-gray-500">{{ $notification['message'] }}</p>
                                        <p class="mt-1 text-xs text-gray-400">{{ $notification['time'] }}</p>
                                    </div>
                                @empty
                                    <p class="mt-2 text-sm text-gray-500">No new notifications</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Stats Overview -->
            <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Total Balance -->
                <div class="overflow-hidden rounded-lg bg-white shadow">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-brown-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="truncate text-sm font-medium text-gray-500">Total Balance</dt>
                                    <dd class="text-lg font-semibold text-gray-900">KES {{ number_format($totalBalance, 2) }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pending Payments -->
                <div class="overflow-hidden rounded-lg bg-white shadow">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="truncate text-sm font-medium text-gray-500">Pending Payments</dt>
                                    <dd class="text-lg font-semibold text-gray-900">{{ $pendingPayments }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                    @can('view-payments')
                    <div class="bg-gray-50 px-5 py-3">
                        <div class="text-sm">
                            <a href="{{ route('payments') }}" class="font-medium text-brown-600 hover:text-brown-900">View all</a>
                        </div>
                    </div>
                    @endcan
                </div>

                <!-- Documents -->
                <div class="overflow-hidden rounded-lg bg-white shadow">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="truncate text-sm font-medium text-gray-500">Recent Documents</dt>
                                    <dd class="text-lg font-semibold text-gray-900">{{ $recentDocuments }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                    @can('view-documents')
                    <div class="bg-gray-50 px-5 py-3">
                        <div class="text-sm">
                            <a href="{{ route('documents') }}" class="font-medium text-brown-600 hover:text-brown-900">View all</a>
                        </div>
                    </div>
                    @endcan
                </div>

                <!-- Contribution Status -->
                <div class="overflow-hidden rounded-lg bg-white shadow">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="truncate text-sm font-medium text-gray-500">Your Contribution Status</dt>
                                    <dd class="text-lg font-semibold text-{{ $contributionStatus === 'Up to date' ? 'green' : 'red' }}-600">
                                        {{ $contributionStatus }}
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- User Stats and Contribution Chart -->
            <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- User Stats -->
                <div class="overflow-hidden rounded-lg bg-white shadow">
                    <div class="p-6">
                        <h3 class="text-base font-semibold leading-6 text-gray-900">Your Statistics</h3>
                        <dl class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <div class="overflow-hidden rounded-lg bg-gray-50 px-4 py-5 sm:p-6">
                                <dt class="truncate text-sm font-medium text-gray-500">Total Contributions</dt>
                                <dd class="mt-1 text-3xl font-semibold text-brown-600">KES {{ number_format($userStats['total_contributions'], 2) }}</dd>
                            </div>
                            <div class="overflow-hidden rounded-lg bg-gray-50 px-4 py-5 sm:p-6">
                                <dt class="truncate text-sm font-medium text-gray-500">Documents Uploaded</dt>
                                <dd class="mt-1 text-3xl font-semibold text-brown-600">{{ $userStats['documents_uploaded'] }}</dd>
                            </div>
                            <div class="overflow-hidden rounded-lg bg-gray-50 px-4 py-5 sm:p-6">
                                <dt class="truncate text-sm font-medium text-gray-500">Last Payment</dt>
                                <dd class="mt-1 text-lg font-semibold text-gray-900">{{ $userStats['last_payment_date'] }}</dd>
                            </div>
                            <div class="overflow-hidden rounded-lg bg-gray-50 px-4 py-5 sm:p-6">
                                <dt class="truncate text-sm font-medium text-gray-500">Member Since</dt>
                                <dd class="mt-1 text-lg font-semibold text-gray-900">{{ $userStats['membership_since'] }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Contribution Chart -->
                <div class="overflow-hidden rounded-lg bg-white shadow">
                    <div class="p-6">
                        <h3 class="text-base font-semibold leading-6 text-gray-900">Monthly Contributions</h3>
                        <div class="mt-6" style="height: 300px;">
                            <canvas id="contributionsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="mt-6">
                <div class="overflow-hidden rounded-lg bg-white shadow">
                    <div class="p-6">
                        <h3 class="text-base font-semibold leading-6 text-gray-900">Recent Activity</h3>
                        
                        <div class="mt-6 flow-root">
                            <ul role="list" class="-mb-8">
                                @foreach($recentActivity as $activity)
                                <li>
                                    <div class="relative pb-8">
                                        @if(!$loop->last)
                                            <span class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                        @endif
                                        <div class="relative flex space-x-3">
                                            <div>
                                                <span class="flex h-8 w-8 items-center justify-center rounded-full {{ $activity->type_color }}">
                                                    {!! $activity->icon !!}
                                                </span>
                                            </div>
                                            <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                                <div>
                                                    <p class="text-sm text-gray-500">{{ $activity->description }}</p>
                                                </div>
                                                <div class="whitespace-nowrap text-right text-sm text-gray-500">
                                                    <time datetime="{{ $activity->created_at->toISOString() }}">
                                                        {{ $activity->created_at->diffForHumans() }}
                                                    </time>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                        
                        <div class="mt-6">
                            <a href="#" class="flex items-center justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-brown-600 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                                View all
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="mt-6">
                <div class="overflow-hidden rounded-lg bg-white shadow">
                    <div class="p-6">
                        <h3 class="text-base font-semibold leading-6 text-gray-900">Quick Actions</h3>
                        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <!-- Always show Submit Payment -->
                            <a href="{{ route('payment-submission') }}" class="flex items-center justify-center rounded-md bg-brown-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brown-500">
                                Submit Payment
                            </a>

                            <!-- Show Upload Document if authorized -->
                            @can('view-documents')
                            <a href="{{ route('documents') }}" class="flex items-center justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-brown-600 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                                Upload Document
                            </a>
                            @endcan

                            <!-- Show Generate Report if authorized -->
                            @can('manage-finances')
                            <a href="{{ route('reports.generate') }}" class="flex items-center justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-brown-600 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                                Generate Report
                            </a>
                            @endcan

                            <!-- Always show Update Profile -->
                            <a href="{{ route('profile') }}" class="flex items-center justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-brown-600 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                                Update Profile
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Upcoming Payments -->
            <div class="mt-6">
                <div class="overflow-hidden rounded-lg bg-white shadow">
                    <div class="p-6">
                        <h3 class="text-base font-semibold leading-6 text-gray-900">Upcoming Payments</h3>
                        <div class="mt-6">
                            <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg">
                                <table class="min-w-full divide-y divide-gray-300">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Description</th>
                                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Amount</th>
                                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Due Date</th>
                                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 bg-white">
                                        @foreach($upcomingPayments as $payment)
                                        <tr>
                                            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
                                                {{ $payment->description }}
                                            </td>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                                KES {{ number_format($payment->amount, 2) }}
                                            </td>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                                {{ $payment->due_date->format('M d, Y') }}
                                            </td>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $payment->status_color }}">
                                                    {{ $payment->status }}
                                                </span>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('contributionsChart').getContext('2d');
            const data = @json($monthlyContributions);
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.map(item => item.month),
                    datasets: [{
                        label: 'Monthly Contributions',
                        data: data.map(item => item.total),
                        backgroundColor: '#92400E',
                        borderColor: '#92400E',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'KES ' + value.toLocaleString();
                                }
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'KES ' + context.raw.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
</x-app-layout>