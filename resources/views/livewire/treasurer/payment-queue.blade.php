<div class="space-y-4">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row gap-4 items-center justify-between">
        <div class="flex-1 w-full">
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Search payments..."
                class="w-full rounded-md border-brown-300 shadow-sm focus:border-brown-500 focus:ring-brown-500 sm:text-sm"
            >
        </div>
        <div class="flex items-center space-x-2">
            <button
                wire:click="showBulkProcessing"
                @class([
                    'px-4 py-2 text-sm font-medium rounded-md shadow-sm',
                    'text-white bg-brown-600 hover:bg-brown-700' => !empty($selected),
                    'text-gray-400 bg-gray-100 cursor-not-allowed' => empty($selected),
                ])
                @disabled(empty($selected))
            >
                Process Selected
            </button>
        </div>
    </div>

    <!-- Transactions List -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-brown-200">
                <thead class="bg-brown-50">
                    <tr>
                        <th scope="col" class="relative w-12 px-6 sm:w-16 sm:px-8">
                            <input
                                type="checkbox"
                                wire:model.live="selectAll"
                                class="absolute left-4 top-1/2 -mt-2 h-4 w-4 rounded border-brown-300 text-brown-600 focus:ring-brown-500"
                            >
                        </th>
                        <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-brown-900">Member</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-brown-900">Description</th>
                        <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-brown-900">Amount</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-brown-900">Date</th>
                        <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-brown-200 bg-white">
                    @forelse ($transactions as $transaction)
                        <tr wire:key="{{ $transaction->id }}">
                            <td class="relative w-12 px-6 sm:w-16 sm:px-8">
                                <input
                                    type="checkbox"
                                    value="{{ $transaction->id }}"
                                    wire:model.live="selected"
                                    class="absolute left-4 top-1/2 -mt-2 h-4 w-4 rounded border-brown-300 text-brown-600 focus:ring-brown-500"
                                >
                            </td>
                            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm">
                                <div class="font-medium text-brown-900">{{ $transaction->user->name }}</div>
                                <div class="text-brown-500">{{ $transaction->user->email }}</div>
                            </td>
                            <td class="px-3 py-4 text-sm text-brown-500">
                                <div class="max-w-xs truncate">{{ $transaction->description }}</div>
                                @if($transaction->tags->isNotEmpty())
                                    <div class="mt-1 flex flex-wrap gap-1">
                                        @foreach($transaction->tags as $tag)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-{{ $tag->color }}-100 text-{{ $tag->color }}-800">
                                                {{ $tag->name }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-right">
                                <span class="font-medium text-brown-900">{{ $transaction->formatted_amount }}</span>
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-brown-500">
                                {{ $transaction->created_at->format('M j, Y g:i A') }}
                            </td>
                            <td class="whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                <div class="flex justify-end space-x-2">
                                    @if($transaction->proof_file)
                                        <a
                                            href="{{ $transaction->getProofUrl() }}"
                                            target="_blank"
                                            class="text-brown-600 hover:text-brown-900"
                                            title="View Proof"
                                        >
                                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" />
                                            </svg>
                                        </a>
                                    @endif
                                    <button
                                        wire:click="showProcessing({{ $transaction->id }})"
                                        class="text-brown-600 hover:text-brown-900"
                                    >
                                        Process
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-4 text-sm text-center text-brown-500">
                                No pending payments found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div>
        {{ $transactions->links() }}
    </div>

    <!-- Processing Modal -->
    <div
        x-data="{ show: @entangle('showProcessingModal') }"
        x-show="show"
        x-cloak
        class="relative z-10"
        aria-labelledby="modal-title"
        role="dialog"
        aria-modal="true"
    >
        <div
            x-show="show"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
        ></div>

        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div
                    x-show="show"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6"
                >
                    <div>
                        <h3 class="text-lg font-medium leading-6 text-brown-900" id="modal-title">
                            Process Payment
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-brown-500">
                                Review and process this payment. Add notes if needed, especially for rejections.
                            </p>
                        </div>
                    </div>

                    <div class="mt-4">
                        <label for="processingNotes" class="block text-sm font-medium text-brown-700">Notes</label>
                        <div class="mt-1">
                            <textarea
                                wire:model="processingNotes"
                                id="processingNotes"
                                rows="3"
                                class="block w-full rounded-md border-brown-300 shadow-sm focus:border-brown-500 focus:ring-brown-500 sm:text-sm"
                                placeholder="Add any notes about this payment..."
                            ></textarea>
                        </div>
                    </div>

                    <div class="mt-5 sm:mt-6 sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-3">
                        <button
                            type="button"
                            wire:click="processTransaction('approved')"
                            class="inline-flex w-full justify-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600 sm:col-start-2"
                        >
                            Approve
                        </button>
                        <button
                            type="button"
                            wire:click="processTransaction('rejected')"
                            class="mt-3 inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:col-start-1 sm:mt-0"
                        >
                            Reject
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Processing Modal -->
    <div
        x-data="{ show: @entangle('showBulkModal') }"
        x-show="show"
        x-cloak
        class="relative z-10"
        aria-labelledby="modal-title"
        role="dialog"
        aria-modal="true"
    >
        <div
            x-show="show"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
        ></div>

        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div
                    x-show="show"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6"
                >
                    <div>
                        <h3 class="text-lg font-medium leading-6 text-brown-900" id="modal-title">
                            Process Multiple Payments
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-brown-500">
                                You are about to process {{ count($selected) }} payment(s).
                            </p>
                        </div>
                    </div>

                    <div class="mt-4">
                        <label for="bulkAction" class="block text-sm font-medium text-brown-700">Action</label>
                        <select
                            wire:model="bulkAction"
                            id="bulkAction"
                            class="mt-1 block w-full rounded-md border-brown-300 shadow-sm focus:border-brown-500 focus:ring-brown-500 sm:text-sm"
                        >
                            <option value="">Select action...</option>
                            <option value="approved">Approve All</option>
                            <option value="rejected">Reject All</option>
                        </select>
                    </div>

                    <div class="mt-4">
                        <label for="bulkNotes" class="block text-sm font-medium text-brown-700">Notes</label>
                        <div class="mt-1">
                            <textarea
                                wire:model="bulkNotes"
                                id="bulkNotes"
                                rows="3"
                                class="block w-full rounded-md border-brown-300 shadow-sm focus:border-brown-500 focus:ring-brown-500 sm:text-sm"
                                placeholder="Add any notes about these payments..."
                            ></textarea>
                        </div>
                    </div>

                    <div class="mt-5 sm:mt-6 sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-3">
                        <button
                            type="button"
                            wire:click="processBulk"
                            class="inline-flex w-full justify-center rounded-md bg-brown-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brown-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brown-600 sm:col-start-2"
                            @disabled(!$bulkAction)
                        >
                            Process Selected
                        </button>
                        <button
                            type="button"
                            wire:click="$set('showBulkModal', false)"
                            class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-brown-900 shadow-sm ring-1 ring-inset ring-brown-300 hover:bg-brown-50 sm:col-start-1 sm:mt-0"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
