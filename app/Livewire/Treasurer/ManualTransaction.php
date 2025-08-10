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

    public function mount()
    {
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

        $transaction = Transaction::create([
            'user_id' => Auth::id(),
            'amount' => $this->type === 'expense' ? -abs($this->amount) : abs($this->amount),
            'type' => $this->type,
            'description' => $this->description,
            'proof_file' => $path,
            'category_id' => $this->category_id,
            'status' => 'approved', // Auto-approve treasurer entries
            'processed_by' => Auth::id(),
            'processed_at' => now(),
            'metadata' => [
                'manual_entry' => true,
                'original_filename' => $this->proof_file ? $this->proof_file->getClientOriginalName() : null,
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
        ]);

        $this->reset(['amount', 'description', 'proof_file', 'selected_tags']);
        $this->dispatch('transaction-created');
    }

    public function render()
    {
        return view('livewire.treasurer.manual-transaction');
    }
}
