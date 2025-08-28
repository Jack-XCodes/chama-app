<?php

namespace App\Livewire\Treasurer;

use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class VerificationQueue extends Component
{
    use WithPagination;

    public $search = '';
    public $selected = [];
    public $selectAll = false;

    // Verification modals
    public $verifyingTransaction = null;
    public $verificationNotes = '';
    public $showVerificationModal = false;

    // Bulk verification
    public $bulkAction = '';
    public $bulkNotes = '';
    public $showBulkModal = false;

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
            ->where('status', Transaction::STATUS_REQUIRES_VERIFICATION)
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

    public function showVerification(Transaction $transaction)
    {
        $this->verifyingTransaction = $transaction;
        $this->verificationNotes = '';
        $this->showVerificationModal = true;
    }

    public function verifyTransaction(string $status)
    {
        $this->validate([
            'verificationNotes' => 'required_if:status,rejected|min:10',
        ]);

        $this->verifyingTransaction->verify(
            $status,
            $this->verificationNotes ?: null,
            Auth::user()
        );

        $this->showVerificationModal = false;
        $this->verifyingTransaction = null;
        $this->verificationNotes = '';

        session()->flash('message', 'Transaction verification completed successfully!');
    }

    public function showBulkVerification()
    {
        if (empty($this->selected)) {
            return;
        }

        $this->bulkAction = '';
        $this->bulkNotes = '';
        $this->showBulkModal = true;
    }

    public function processBulkVerification()
    {
        $this->validate([
            'bulkAction' => 'required|in:approved,rejected',
            'bulkNotes' => 'required_if:bulkAction,rejected|min:10',
        ]);

        Transaction::bulkVerify(
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

        session()->flash('message', 'Bulk verification completed successfully!');
    }

    public function render()
    {
        return view('livewire.treasurer.verification-queue', [
            'transactions' => $this->getFilteredTransactions()
                ->with(['user', 'tags', 'category', 'processor'])
                ->latest()
                ->paginate(10),
        ]);
    }
}