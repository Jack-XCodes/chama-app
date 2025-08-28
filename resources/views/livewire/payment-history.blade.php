<div class="space-y-4">
    <!-- Search & Filter -->
    <div class="flex flex-col sm:flex-row gap-4">
        <div class="flex-1">
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Search payments..."
                class="w-full rounded-md border-brown-300 shadow-sm focus:border-brown-500 focus:ring-brown-500 sm:text-sm"
            >
        </div>
        <div class="sm:w-48">
            <select
                wire:model.live="status"
                class="w-full rounded-md border-brown-300 shadow-sm focus:border-brown-500 focus:ring-brown-500 sm:text-sm"
            >
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
            </select>
        </div>
    </div>

    <!-- Transactions List -->
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
        <ul role="list" class="divide-y divide-brown-200">
            @forelse ($transactions as $transaction)
                <li class="p-4 hover:bg-brown-50">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-medium text-brown-900 truncate">
                                    {{ $transaction->description }}
                                </p>
                                <div class="ml-2">
                                    <span @class([
                                        'px-2 inline-flex text-xs leading-5 font-semibold rounded-full',
                                        'bg-yellow-100 text-yellow-800' => $transaction->status === 'pending',
                                        'bg-green-100 text-green-800' => $transaction->status === 'approved',
                                        'bg-red-100 text-red-800' => $transaction->status === 'rejected',
                                    ])>
                                        {{ ucfirst($transaction->status) }}
                                    </span>
                                </div>
                            </div>
                            <div class="mt-2 flex items-center justify-between">
                                <div class="sm:flex sm:items-center">
                                    <p class="text-sm text-brown-500">
                                        {{ $transaction->created_at->format('M j, Y g:i A') }}
                                    </p>
                                    @if($transaction->processed_at)
                                        <div class="hidden sm:flex sm:mx-2 text-brown-500">&middot;</div>
                                        <p class="text-sm text-brown-500">
                                            Processed: {{ $transaction->processed_at->format('M j, Y g:i A') }}
                                        </p>
                                    @endif
                                </div>
                                <p class="text-sm font-medium text-brown-900">
                                    {{ $transaction->formatted_amount }}
                                </p>
                            </div>
                            @if($transaction->treasurer_notes)
                                <div class="mt-2 text-sm text-brown-600">
                                    <strong>Notes:</strong> {{ $transaction->treasurer_notes }}
                                </div>
                            @endif
                        </div>
                        @if($transaction->proof_file)
                            <div class="ml-4">
                                <a
                                    href="{{ $transaction->getProofUrl() }}"
                                    target="_blank"
                                    class="text-brown-600 hover:text-brown-900"
                                >
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" />
                                    </svg>
                                </a>
                            </div>
                        @endif
                    </div>
                </li>
            @empty
                <li class="p-4">
                    <p class="text-center text-brown-500">No payments found.</p>
                </li>
            @endforelse
        </ul>
    </div>

    <!-- Pagination -->
    <div>
        {{ $transactions->links() }}
    </div>
</div>
