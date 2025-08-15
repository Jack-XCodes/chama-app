<div class="max-w-lg mx-auto">
    <div class="bg-white shadow sm:rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-medium leading-6 text-gray-900">Submit Payment</h3>
            
            <form wire:submit="submit" class="mt-5 space-y-6">
                <!-- Amount Input -->
                <div>
                    <label for="amount" class="block text-sm font-medium text-gray-700">
                        Amount (KES)
                    </label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 sm:text-sm">KES</span>
                        </div>
                        <input
                            type="number"
                            inputmode="decimal"
                            name="amount"
                            id="amount"
                            wire:model="amount"
                            class="block w-full pl-12 pr-4 py-3 text-lg border-gray-300 rounded-md focus:ring-brown-500 focus:border-brown-500"
                            placeholder="0.00"
                            step="0.01"
                            min="0"
                            required
                            autocomplete="off"
                        >
                    </div>
                    @error('amount')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description Input -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">
                        Description
                    </label>
                    <div class="mt-1">
                        <input
                            type="text"
                            name="description"
                            id="description"
                            wire:model="description"
                            class="block w-full py-3 border-gray-300 rounded-md focus:ring-brown-500 focus:border-brown-500"
                            placeholder="Monthly contribution for..."
                            required
                            autocomplete="off"
                        >
                    </div>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Reference Number Input -->
                <div>
                    <label for="reference" class="block text-sm font-medium text-gray-700">
                        M-PESA Reference Number
                    </label>
                    <div class="mt-1">
                        <input
                            type="text"
                            name="reference"
                            id="reference"
                            wire:model="reference"
                            class="block w-full py-3 border-gray-300 rounded-md uppercase focus:ring-brown-500 focus:border-brown-500"
                            placeholder="QK7PXDB4YM"
                            pattern="[A-Z0-9]+"
                            required
                            autocomplete="off"
                            maxlength="10"
                            minlength="10"
                        >
                    </div>
                    @error('reference')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Proof File Upload -->
                <div>
                    <label for="proof" class="block text-sm font-medium text-gray-700">
                        Upload M-PESA Screenshot
                    </label>
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                        <div class="space-y-1 text-center">
                            @if ($proof_file)
                                <div class="relative">
                                    @if(in_array($proof_file->getClientOriginalExtension(), ['jpg', 'jpeg', 'png', 'gif']))
                                        <img src="{{ $proof_file->temporaryUrl() }}" 
                                             alt="Payment Proof Preview" 
                                             class="mx-auto h-32 w-auto object-cover rounded-lg">
                                    @else
                                        <div class="mx-auto h-32 w-32 flex items-center justify-center bg-gray-100 rounded-lg">
                                            <x-heroicon-o-document class="h-16 w-16 text-gray-400" />
                                        </div>
                                    @endif
                                    <button type="button" 
                                            wire:click="$set('proof_file', null)"
                                            class="absolute top-0 right-0 -mt-2 -mr-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600">
                                        <x-heroicon-m-x-mark class="h-4 w-4" />
                                    </button>
                                </div>
                            @else
                                <div class="flex flex-col items-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <div class="flex text-sm text-gray-600">
                                        <label for="proof" class="relative cursor-pointer bg-white rounded-md font-medium text-brown-600 hover:text-brown-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-brown-500">
                                            <span>Upload a file</span>
                                            <input
                                                id="proof"
                                                name="proof"
                                                type="file"
                                                wire:model="proof_file"
                                                class="sr-only"
                                                accept="image/*,.pdf"
                                                capture="environment"
                                            >
                                        </label>
                                        <p class="pl-1">or drag and drop</p>
                                    </div>
                                    <p class="text-xs text-gray-500">
                                        PNG, JPG, GIF up to 10MB
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>
                    @error('proof_file')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror

                    <div wire:loading wire:target="proof_file" class="mt-2">
                        <div class="flex items-center text-sm text-gray-500">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-brown-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Uploading...
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end">
                    <button
                        type="submit"
                        class="w-full inline-flex justify-center py-3 px-4 border border-transparent shadow-sm text-lg font-medium rounded-md text-white bg-brown-600 hover:bg-brown-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brown-500"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-75 cursor-not-allowed"
                    >
                        <span wire:loading.remove>Submit Payment</span>
                        <span wire:loading>
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Processing...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Success Modal -->
    <div
        x-data="{ show: @entangle('showSuccessModal') }"
        x-show="show"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed z-50 inset-0 overflow-y-auto"
        x-cloak
    >
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div
                x-show="show"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 transition-opacity"
                aria-hidden="true"
            >
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div
                x-show="show"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-sm sm:w-full sm:p-6"
            >
                <div>
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                        <svg class="h-6 w-6 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div class="mt-3 text-center sm:mt-5">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            Payment Submitted Successfully!
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">
                                Your payment has been submitted and is pending approval. You will be notified once it's processed.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="mt-5 sm:mt-6">
                    <button
                        type="button"
                        class="inline-flex justify-center w-full rounded-md border border-transparent shadow-sm px-4 py-2 bg-brown-600 text-base font-medium text-white hover:bg-brown-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brown-500 sm:text-sm"
                        wire:click="resetForm"
                    >
                        Submit Another Payment
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>