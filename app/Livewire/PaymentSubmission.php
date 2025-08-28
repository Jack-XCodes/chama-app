<?php

namespace App\Livewire;

use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Rule;

class PaymentSubmission extends Component
{
    use WithFileUploads;

    #[Rule('required|numeric|min:1')]
    public $amount = '';

    #[Rule('required|string|min:3')]
    public $description = '';

    #[Rule('required|file|mimes:jpg,jpeg,png,pdf|max:5120')] // 5MB max
    public $proof_file;

    public function submit()
    {
        $this->validate();

        $path = $this->proof_file->store('payment-proofs', 'private');

        $transaction = Transaction::create([
            'user_id' => Auth::id(),
            'amount' => $this->amount,
            'type' => 'payment',
            'description' => $this->description,
            'proof_file' => $path,
            'status' => 'pending',
            'metadata' => [
                'original_filename' => $this->proof_file->getClientOriginalName(),
                'mime_type' => $this->proof_file->getMimeType(),
                'size' => $this->proof_file->getSize(),
            ],
        ]);

        $this->reset(['amount', 'description', 'proof_file']);

        $this->dispatch('payment-submitted', $transaction->id);
    }

    public function render()
    {
        return view('livewire.payment-submission');
    }
}
