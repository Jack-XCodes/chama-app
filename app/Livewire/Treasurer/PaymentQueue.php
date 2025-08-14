<?php

namespace App\Livewire\Treasurer;

use App\Models\Transaction;
use App\Models\TransactionTag;
use App\Models\TransactionCategory;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class PaymentQueue extends Component
{
    use WithPagination;

    public $search = '';
    public $selected = [];
    public $selectAll = false;
    public $bulkAction = '';
    public $bulkNotes = '';
    public $showBulkModal = false;

    public $processingTransaction = null;
    public $processingNotes = '';
    public $showProcessingModal = false;

    // Filtering options
    public $filterType = '';
    public $filterCategory = '';
    public $filterAmountMin = '';
    public $filterAmountMax = '';
    public $filterDateFrom = '';
    public $filterDateTo = '';
    public $showFilters = false;

    // Bulk tagging
    public $showBulkTagModal = false;
    public $selectedTag = '';
    public $showBulkCategoryModal = false;
    public $selectedCategory = '';

    // Verification
    public $verifyingTransaction = null;
    public $verificationNotes = '';
    public $showVerificationModal = false;

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selected = $this->getFilteredTransactions()
                ->pluck('id')
                ->map(fn($id) => (string) $id)
                ->toArray();
        } else {
            $this->selected = [];
        }
    }

    public function updatedSelected()
    {
        $this->selectAll = false;
    }

    protected function getFilteredTransactions()
    {
        return Transaction::query()
            ->whereIn('status', [Transaction::STATUS_PENDING, Transaction::STATUS_REQUIRES_VERIFICATION])
            ->when($this->filterType, function ($query) {
                $query->where('type', $this->filterType);
            })
            ->when($this->filterCategory, function ($query) {
                $query->where('category_id', $this->filterCategory);
            })
            ->when($this->filterAmountMin, function ($query) {
                $query->where('amount', '>=', $this->filterAmountMin);
            })
            ->when($this->filterAmountMax, function ($query) {
                $query->where('amount', '<=', $this->filterAmountMax);
            })
            ->when($this->filterDateFrom, function ($query) {
                $query->whereDate('created_at', '>=', $this->filterDateFrom);
            })
            ->when($this->filterDateTo, function ($query) {
                $query->whereDate('created_at', '<=', $this->filterDateTo);
            })
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('description', 'like', '%' . $this->search . '%')
                        ->orWhere('amount', 'like', '%' . $this->search . '%')
                        ->orWhere('reference_number', 'like', '%' . $this->search . '%')
                        ->orWhereHas('user', function ($query) {
                            $query->where('name', 'like', '%' . $this->search . '%')
                                ->orWhere('email', 'like', '%' . $this->search . '%');
                        });
                });
            });
    }

    public function showProcessing(Transaction $transaction)
    {
        $this->processingTransaction = $transaction;
        $this->processingNotes = '';
        $this->showProcessingModal = true;
    }

    public function processTransaction(string $status)
    {
        $this->validate([
            'processingNotes' => 'required_if:status,rejected',
        ]);

        $this->processingTransaction->process(
            $status,
            $this->processingNotes ?: null,
            Auth::user()
        );

        $this->showProcessingModal = false;
        $this->processingTransaction = null;
        $this->processingNotes = '';
    }

    public function showBulkProcessing()
    {
        if (empty($this->selected)) {
            return;
        }

        $this->bulkAction = '';
        $this->bulkNotes = '';
        $this->showBulkModal = true;
    }

    public function processBulk()
    {
        $this->validate([
            'bulkAction' => 'required|in:approved,rejected',
            'bulkNotes' => 'required_if:bulkAction,rejected',
        ]);

        Transaction::bulkProcess(
            $this->selected,
            $this->bulkAction,
            $this->bulkNotes ?: null,
            Auth::user()
        );

        $this->showBulkModal = false;
        $this->selected = [];
        $this->selectAll = false;
        $this->bulkAction = '';
        $this->bulkNotes = '';
    }

    public function showVerification(Transaction $transaction)
    {
        $this->verifyingTransaction = $transaction;
        $this->verificationNotes = '';
        $this->showVerificationModal = true;
    }

    public function verifyTransaction(string $status)
    {
        $this->validate([
            'verificationNotes' => 'required_if:status,rejected',
        ]);

        $this->verifyingTransaction->verify(
            $status,
            $this->verificationNotes ?: null,
            Auth::user()
        );

        $this->showVerificationModal = false;
        $this->verifyingTransaction = null;
        $this->verificationNotes = '';
    }

    public function showBulkTagging()
    {
        if (empty($this->selected)) {
            return;
        }
        $this->showBulkTagModal = true;
    }

    public function applyBulkTag()
    {
        $this->validate([
            'selectedTag' => 'required|exists:transaction_tags,id',
        ]);

        $tag = TransactionTag::find($this->selectedTag);
        Transaction::bulkTag($this->selected, $tag);

        $this->showBulkTagModal = false;
        $this->selectedTag = '';
        $this->selected = [];
        $this->selectAll = false;
    }

    public function showBulkCategorization()
    {
        if (empty($this->selected)) {
            return;
        }
        $this->showBulkCategoryModal = true;
    }

    public function applyBulkCategory()
    {
        $this->validate([
            'selectedCategory' => 'required|exists:transaction_categories,id',
        ]);

        $category = TransactionCategory::find($this->selectedCategory);
        Transaction::bulkCategorize($this->selected, $category);

        $this->showBulkCategoryModal = false;
        $this->selectedCategory = '';
        $this->selected = [];
        $this->selectAll = false;
    }

    public function clearFilters()
    {
        $this->filterType = '';
        $this->filterCategory = '';
        $this->filterAmountMin = '';
        $this->filterAmountMax = '';
        $this->filterDateFrom = '';
        $this->filterDateTo = '';
    }

    public function render()
    {
        return view('livewire.treasurer.payment-queue', [
            'transactions' => $this->getFilteredTransactions()
                ->with(['user', 'tags', 'category', 'processor', 'verifier'])
                ->latest()
                ->paginate(10),
            'transactionTypes' => Transaction::getTransactionTypes(),
            'categories' => TransactionCategory::active()->orderBy('name')->get(),
            'tags' => TransactionTag::active()->orderBy('name')->get(),
        ]);
    }
}
