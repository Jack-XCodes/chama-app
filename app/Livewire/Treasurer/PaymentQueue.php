<?php

namespace App\Livewire\Treasurer;

use App\Models\Transaction;
use App\Models\TransactionTag;
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
            ->where('type', 'payment')
            ->where('status', 'pending')
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('description', 'like', '%' . $this->search . '%')
                        ->orWhere('amount', 'like', '%' . $this->search . '%')
                        ->orWhereHas('user', function ($query) {
                            $query->where('name', 'like', '%' . $this->search . '%');
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

    public function render()
    {
        return view('livewire.treasurer.payment-queue', [
            'transactions' => $this->getFilteredTransactions()
                ->with(['user', 'tags'])
                ->latest()
                ->paginate(10),
        ]);
    }
}
