<?php

namespace App\Livewire\Treasurer;

use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\TransactionTag;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Rule;

class ManualTransaction extends Component
{
    use WithFileUploads;

    #[Rule('required|numeric|min:0.01')]
    public $amount = '';

    #[Rule('required|string|min:3')]
    public $description = '';

    #[Rule('required|string')]
    public $type = 'expense';

    #[Rule('required|exists:transaction_categories,id')]
    public $category_id = '';

    public $selected_tags = [];

    #[Rule('nullable|file|mimes:jpg,jpeg,png,pdf|max:5120')] // 5MB max
    public $proof_file;

    #[Rule('nullable|string')]
    public $reference_number = '';

    #[Rule('nullable|date')]
    public $transaction_date = '';

    public $requires_verification = false;

    public function mount()
    {
        $this->transaction_date = now()->format('Y-m-d');
        $this->loadCategories();
        $this->loadTags();
    }

    public function loadCategories()
    {
        $this->categories = TransactionCategory::active()
            ->ofType($this->type)
            ->orderBy('name')
            ->get();

        if ($this->categories->isNotEmpty() && !$this->category_id) {
            $this->category_id = $this->categories->first()->id;
        }
    }

    public function loadTags()
    {
        $this->tags = TransactionTag::active()
            ->orderBy('name')
            ->get();
    }

    public function updatedType()
    {
        $this->loadCategories();
        $this->category_id = '';
    }

    public function submit()
    {
        $this->validate();

        $path = null;
        if ($this->proof_file) {
            $path = $this->proof_file->store('transaction-proofs', 'private');
        }

        // Determine amount sign based on transaction type
        $amount = $this->amount;
        if (in_array($this->type, [Transaction::TYPE_EXPENSE, Transaction::TYPE_BANK_CHARGE])) {
            $amount = -abs($this->amount);
        } else {
            $amount = abs($this->amount);
        }

        // Check if verification is required
        $status = Transaction::STATUS_APPROVED;
        if ($this->requires_verification || abs($amount) >= Transaction::VERIFICATION_THRESHOLD) {
            $status = Transaction::STATUS_REQUIRES_VERIFICATION;
        }

        $transaction = Transaction::create([
            'user_id' => Auth::id(),
            'amount' => $amount,
            'type' => $this->type,
            'description' => $this->description,
            'proof_file' => $path,
            'category_id' => $this->category_id,
            'status' => $status,
            'processed_by' => Auth::id(),
            'processed_at' => now(),
            'reference_number' => $this->reference_number,
            'transaction_date' => $this->transaction_date,
            'requires_verification' => $this->requires_verification || abs($amount) >= Transaction::VERIFICATION_THRESHOLD,
            'metadata' => [
                'manual_entry' => true,
                'original_filename' => $this->proof_file ? $this->proof_file->getClientOriginalName() : null,
                'entry_method' => 'treasurer_manual',
            ],
        ]);

        // Add selected tags
        foreach ($this->selected_tags as $tagId) {
            $tag = TransactionTag::find($tagId);
            if ($tag) {
                $transaction->addTag($tag);
            }
        }

        // Log the creation
        $transaction->logAudit('manual_entry_created', [
            'type' => $this->type,
            'category_id' => $this->category_id,
            'tags' => $this->selected_tags,
            'reference_number' => $this->reference_number,
            'requires_verification' => $transaction->requires_verification,
        ]);

        $this->reset(['amount', 'description', 'proof_file', 'selected_tags', 'reference_number']);
        $this->transaction_date = now()->format('Y-m-d');
        $this->requires_verification = false;
        
        session()->flash('message', 'Transaction created successfully!');
        $this->dispatch('transaction-created');
    }

    public function render()
    {
        return view('livewire.treasurer.manual-transaction', [
            'transactionTypes' => Transaction::getTransactionTypes(),
        ]);
    }
}
