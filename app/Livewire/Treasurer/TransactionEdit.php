<?php

namespace App\Livewire\Treasurer;

use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\TransactionTag;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Rule;

class TransactionEdit extends Component
{
    use WithFileUploads;

    public Transaction $transaction;

    #[Rule('required|numeric|min:0.01')]
    public $amount = '';

    #[Rule('required|string|min:3')]
    public $description = '';

    #[Rule('required|string')]
    public $type = '';

    #[Rule('required|exists:transaction_categories,id')]
    public $category_id = '';

    public $selected_tags = [];

    #[Rule('nullable|file|mimes:jpg,jpeg,png,pdf|max:5120')]
    public $proof_file;

    #[Rule('nullable|string')]
    public $reference_number = '';

    #[Rule('nullable|date')]
    public $transaction_date = '';

    public $requires_verification = false;

    #[Rule('required|string|min:10')]
    public $edit_reason = '';

    public $showConfirmModal = false;

    public function mount(Transaction $transaction)
    {
        $this->transaction = $transaction;
        $this->amount = abs($transaction->amount);
        $this->description = $transaction->description;
        $this->type = $transaction->type;
        $this->category_id = $transaction->category_id;
        $this->selected_tags = $transaction->tags->pluck('id')->toArray();
        $this->reference_number = $transaction->reference_number;
        $this->transaction_date = $transaction->transaction_date?->format('Y-m-d');
        $this->requires_verification = $transaction->requires_verification;
    }

    public function showConfirmation()
    {
        $this->validate();
        $this->showConfirmModal = true;
    }

    public function saveChanges()
    {
        $this->validate();

        // Store original values for audit
        $originalValues = [
            'amount' => $this->transaction->amount,
            'description' => $this->transaction->description,
            'type' => $this->transaction->type,
            'category_id' => $this->transaction->category_id,
            'reference_number' => $this->transaction->reference_number,
            'transaction_date' => $this->transaction->transaction_date,
            'requires_verification' => $this->transaction->requires_verification,
            'tags' => $this->transaction->tags->pluck('id')->toArray(),
        ];

        // Handle file upload
        $path = $this->transaction->proof_file;
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
        $requiresVerification = $this->requires_verification || abs($amount) >= Transaction::VERIFICATION_THRESHOLD;

        // Update transaction
        $this->transaction->update([
            'amount' => $amount,
            'description' => $this->description,
            'type' => $this->type,
            'category_id' => $this->category_id,
            'reference_number' => $this->reference_number,
            'transaction_date' => $this->transaction_date,
            'requires_verification' => $requiresVerification,
            'proof_file' => $path,
        ]);

        // Update tags
        $this->transaction->tags()->detach();
        foreach ($this->selected_tags as $tagId) {
            $tag = TransactionTag::find($tagId);
            if ($tag) {
                $this->transaction->addTag($tag);
            }
        }

        // Log the edit with detailed audit trail
        $newValues = [
            'amount' => $this->transaction->amount,
            'description' => $this->transaction->description,
            'type' => $this->transaction->type,
            'category_id' => $this->transaction->category_id,
            'reference_number' => $this->transaction->reference_number,
            'transaction_date' => $this->transaction->transaction_date,
            'requires_verification' => $this->transaction->requires_verification,
            'tags' => $this->selected_tags,
        ];

        $this->transaction->logAudit('transaction_edited', [
            'edit_reason' => $this->edit_reason,
            'original_values' => $originalValues,
            'new_values' => $newValues,
            'changed_fields' => $this->getChangedFields($originalValues, $newValues),
            'editor_id' => Auth::id(),
        ]);

        $this->showConfirmModal = false;
        session()->flash('message', 'Transaction updated successfully!');
        $this->dispatch('transaction-updated');
    }

    private function getChangedFields(array $original, array $new): array
    {
        $changed = [];
        foreach ($new as $key => $value) {
            if ($original[$key] != $value) {
                $changed[$key] = [
                    'from' => $original[$key],
                    'to' => $value,
                ];
            }
        }
        return $changed;
    }

    public function render()
    {
        return view('livewire.treasurer.transaction-edit', [
            'transactionTypes' => Transaction::getTransactionTypes(),
            'categories' => TransactionCategory::active()->orderBy('name')->get(),
            'tags' => TransactionTag::active()->orderBy('name')->get(),
        ]);
    }
}