<div class="p-4 sm:p-6 bg-white rounded-lg shadow">
    <h2 class="text-lg font-semibold text-brown-800 mb-4">Submit Payment</h2>

    <form wire:submit="submit" class="space-y-4">
        <!-- Amount -->
        <div>
            <label for="amount" class="block text-sm font-medium text-brown-700">Amount (KES)</label>
            <div class="mt-1">
                <input
                    type="number"
                    step="0.01"
                    min="1"
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
            <label for="description" class="block text-sm font-medium text-brown-700">Payment Description</label>
            <div class="mt-1">
                <textarea
                    wire:model="description"
                    id="description"
                    rows="3"
                    class="block w-full rounded-md border-brown-300 shadow-sm focus:border-brown-500 focus:ring-brown-500 sm:text-sm"
                    placeholder="What is this payment for?"
                ></textarea>
            </div>
            @error('description')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Proof File -->
        <div>
            <label for="proof_file" class="block text-sm font-medium text-brown-700">Payment Proof</label>
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
            <p class="mt-1 text-sm text-brown-500">Upload a receipt or screenshot (JPG, PNG, or PDF, max 5MB)</p>
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
                <span wire:loading.remove>Submit Payment</span>
                <span wire:loading>Processing...</span>
            </button>
        </div>
    </form>

    <!-- Success Message -->
    <div
        x-data="{ show: false, timeout: null }"
        x-show="show"
        x-init="@this.on('payment-submitted', () => {
            clearTimeout(timeout);
            show = true;
            timeout = setTimeout(() => show = false, 3000);
        })"
        x-transition
        class="fixed bottom-4 right-4 bg-green-50 text-green-800 px-4 py-3 rounded-lg shadow-lg"
        style="display: none;"
    >
        Payment submitted successfully!
    </div>
</div>
