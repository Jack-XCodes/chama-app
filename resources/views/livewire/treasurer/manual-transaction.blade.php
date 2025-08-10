<div class="p-4 sm:p-6 bg-white rounded-lg shadow">
    <h2 class="text-lg font-semibold text-brown-800 mb-4">Record Transaction</h2>

    <form wire:submit="submit" class="space-y-4">
        <!-- Type -->
        <div>
            <label class="block text-sm font-medium text-brown-700">Transaction Type</label>
            <div class="mt-1">
                <div class="flex space-x-4">
                    <label class="inline-flex items-center">
                        <input type="radio" wire:model.live="type" value="expense" class="text-brown-600 focus:ring-brown-500">
                        <span class="ml-2">Expense</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" wire:model.live="type" value="income" class="text-brown-600 focus:ring-brown-500">
                        <span class="ml-2">Income</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Amount -->
        <div>
            <label for="amount" class="block text-sm font-medium text-brown-700">Amount (KES)</label>
            <div class="mt-1">
                <input
                    type="number"
                    step="0.01"
                    min="0.01"
                    wire:model="amount"
                    id="amount"
                    class="block w-full rounded-md border-brown-300 shadow-sm focus:border-brown-500 focus:ring-brown-500 sm:text-sm"
                    placeholder="0.00"
                >
            </div>
            @error('amount')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Description -->
        <div>
            <label for="description" class="block text-sm font-medium text-brown-700">Description</label>
            <div class="mt-1">
                <textarea
                    wire:model="description"
                    id="description"
                    rows="3"
                    class="block w-full rounded-md border-brown-300 shadow-sm focus:border-brown-500 focus:ring-brown-500 sm:text-sm"
                    placeholder="What is this transaction for?"
                ></textarea>
            </div>
            @error('description')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Category -->
        <div>
            <label for="category_id" class="block text-sm font-medium text-brown-700">Category</label>
            <div class="mt-1">
                <select
                    wire:model="category_id"
                    id="category_id"
                    class="block w-full rounded-md border-brown-300 shadow-sm focus:border-brown-500 focus:ring-brown-500 sm:text-sm"
                >
                    <option value="">Select a category</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            @error('category_id')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Tags -->
        <div>
            <label class="block text-sm font-medium text-brown-700 mb-2">Tags</label>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                @foreach($tags as $tag)
                    <label class="inline-flex items-center p-2 rounded-md border border-brown-200 hover:bg-brown-50">
                        <input
                            type="checkbox"
                            wire:model="selected_tags"
                            value="{{ $tag->id }}"
                            class="text-brown-600 focus:ring-brown-500"
                        >
                        <span class="ml-2 text-sm">{{ $tag->name }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <!-- Proof File -->
        <div>
            <label for="proof_file" class="block text-sm font-medium text-brown-700">Proof Document (Optional)</label>
            <div class="mt-1">
                <input
                    type="file"
                    wire:model="proof_file"
                    id="proof_file"
                    accept=".jpg,.jpeg,.png,.pdf"
                    class="block w-full text-sm text-brown-500
                        file:mr-4 file:py-2 file:px-4
                        file:rounded-md file:border-0
                        file:text-sm file:font-semibold
                        file:bg-brown-50 file:text-brown-700
                        hover:file:bg-brown-100"
                >
            </div>
            <p class="mt-1 text-sm text-brown-500">Upload a receipt or document (JPG, PNG, or PDF, max 5MB)</p>
            @error('proof_file')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="pt-4">
            <button
                type="submit"
                class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-brown-600 hover:bg-brown-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brown-500"
                wire:loading.attr="disabled"
                wire:loading.class="opacity-75 cursor-wait"
            >
                <span wire:loading.remove>Record Transaction</span>
                <span wire:loading>Processing...</span>
            </button>
        </div>
    </form>

    <!-- Success Message -->
    <div
        x-data="{ show: false, timeout: null }"
        x-show="show"
        x-init="@this.on('transaction-created', () => {
            clearTimeout(timeout);
            show = true;
            timeout = setTimeout(() => show = false, 3000);
        })"
        x-transition
        class="fixed bottom-4 right-4 bg-green-50 text-green-800 px-4 py-3 rounded-lg shadow-lg"
        style="display: none;"
    >
        Transaction recorded successfully!
    </div>
</div>
