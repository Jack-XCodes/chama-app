<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Notifications\PaymentStatusChanged;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'amount',
        'type',
        'description',
        'proof_file',
        'status',
        'treasurer_notes',
        'category_id',
        'processed_by',
        'processed_at',
        'metadata',
        'audit_trail',
        'requires_verification',
        'verified_by',
        'verified_at',
        'verification_notes',
        'reference_number',
        'transaction_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'audit_trail' => 'array',
        'processed_at' => 'datetime',
        'requires_verification' => 'boolean',
        'verified_at' => 'datetime',
        'transaction_date' => 'date',
    ];

    // Transaction types
    const TYPE_PAYMENT = 'payment';
    const TYPE_EXPENSE = 'expense';
    const TYPE_INCOME = 'income';
    const TYPE_INVESTMENT = 'investment';
    const TYPE_BANK_CHARGE = 'bank_charge';
    const TYPE_TRANSFER = 'transfer';
    const TYPE_REFUND = 'refund';

    // Transaction statuses
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_REQUIRES_VERIFICATION = 'requires_verification';
    const STATUS_VERIFIED = 'verified';

    // Verification threshold amount
    const VERIFICATION_THRESHOLD = 50000; // KES 50,000

    /**
     * Get the user who made the transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the treasurer who processed the transaction.
     */
    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Get the user who verified the transaction.
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Get the category of the transaction.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(TransactionCategory::class);
    }

    /**
     * Get the tags for this transaction.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(TransactionTag::class, 'transaction_tag')
            ->withPivot('tagged_by', 'tagged_at')
            ->using(TransactionTagPivot::class);
    }

    /**
     * Add a tag to the transaction.
     */
    public function addTag(TransactionTag $tag): void
    {
        if (!$this->tags->contains($tag->id)) {
            $this->tags()->attach($tag->id, [
                'tagged_by' => Auth::id(),
                'tagged_at' => now(),
            ]);
        }
    }

    /**
     * Remove a tag from the transaction.
     */
    public function removeTag(TransactionTag $tag): void
    {
        $this->tags()->detach($tag->id);
    }

    /**
     * Log an audit entry.
     */
    public function logAudit(string $action, array $data = []): void
    {
        $audit = $this->audit_trail ?? [];
        $audit[] = [
            'action' => $action,
            'user_id' => Auth::id(),
            'timestamp' => now()->toIso8601String(),
            'data' => $data,
        ];
        $this->update(['audit_trail' => $audit]);
    }

    /**
     * Get the proof file URL
     */
    public function getProofUrl(): ?string
    {
        return $this->proof_file
            ? Storage::disk('private')->url($this->proof_file)
            : null;
    }

    /**
     * Format amount with currency
     */
    public function getFormattedAmountAttribute(): string
    {
        return 'KES ' . number_format($this->amount, 2);
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'approved' => 'success',
            'rejected' => 'danger',
            default => 'warning',
        };
    }

    /**
     * Scope a query to only include user's transactions
     */
    public function scopeForUser($query, User $user)
    {
        if ($user->hasRole('admin') || $user->hasRole('treasurer')) {
            return $query;
        }
        
        return $query->where('user_id', $user->id);
    }

    /**
     * Scope a query to only include pending transactions
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include transactions requiring verification
     */
    public function scopeRequiresVerification($query)
    {
        return $query->where('status', 'requires_verification')
            ->orWhere('requires_verification', true);
    }

    /**
     * Scope a query to only include transactions of a specific type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to only include large transactions
     */
    public function scopeLargeTransactions($query)
    {
        return $query->where('amount', '>=', self::VERIFICATION_THRESHOLD)
            ->orWhere('amount', '<=', -self::VERIFICATION_THRESHOLD);
    }

    /**
     * Check if transaction can be processed
     */
    public function canBeProcessed(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_REQUIRES_VERIFICATION]);
    }

    /**
     * Check if transaction requires verification
     */
    public function requiresVerification(): bool
    {
        return $this->requires_verification || 
               abs($this->amount) >= self::VERIFICATION_THRESHOLD ||
               $this->status === self::STATUS_REQUIRES_VERIFICATION;
    }

    /**
     * Check if transaction can be verified
     */
    public function canBeVerified(): bool
    {
        return $this->status === self::STATUS_REQUIRES_VERIFICATION && 
               !$this->verified_at;
    }

    /**
     * Get transaction type display name
     */
    public function getTypeDisplayAttribute(): string
    {
        return match($this->type) {
            self::TYPE_PAYMENT => 'Member Payment',
            self::TYPE_EXPENSE => 'Expense',
            self::TYPE_INCOME => 'Income',
            self::TYPE_INVESTMENT => 'Investment',
            self::TYPE_BANK_CHARGE => 'Bank Charge',
            self::TYPE_TRANSFER => 'Transfer',
            self::TYPE_REFUND => 'Refund',
            default => ucfirst($this->type),
        };
    }

    /**
     * Process the transaction
     */
    public function process(string $status, ?string $notes = null, User $processor): void
    {
        if (!$this->canBeProcessed()) {
            throw new \Exception('Transaction cannot be processed');
        }

        $oldStatus = $this->status;
        $oldNotes = $this->treasurer_notes;

        // Check if large transaction needs verification
        $finalStatus = $status;
        if ($status === self::STATUS_APPROVED && $this->requiresVerification()) {
            $finalStatus = self::STATUS_REQUIRES_VERIFICATION;
        }

        $this->update([
            'status' => $finalStatus,
            'treasurer_notes' => $notes,
            'processed_by' => $processor->id,
            'processed_at' => now(),
            'requires_verification' => $this->requiresVerification(),
        ]);

        // Log the status change
        $this->logAudit('status_changed', [
            'old_status' => $oldStatus,
            'new_status' => $finalStatus,
            'old_notes' => $oldNotes,
            'new_notes' => $notes,
            'requires_verification' => $this->requiresVerification(),
        ]);

        // Send notification to the user only if not requiring verification
        if ($finalStatus !== self::STATUS_REQUIRES_VERIFICATION) {
            $this->user->notify(new PaymentStatusChanged($this));
        }
    }

    /**
     * Verify the transaction
     */
    public function verify(string $status, ?string $notes = null, User $verifier): void
    {
        if (!$this->canBeVerified()) {
            throw new \Exception('Transaction cannot be verified');
        }

        $oldStatus = $this->status;

        $this->update([
            'status' => $status,
            'verification_notes' => $notes,
            'verified_by' => $verifier->id,
            'verified_at' => now(),
        ]);

        // Log the verification
        $this->logAudit('verification_completed', [
            'old_status' => $oldStatus,
            'new_status' => $status,
            'verification_notes' => $notes,
            'verifier_id' => $verifier->id,
        ]);

        // Send notification to the user
        $this->user->notify(new PaymentStatusChanged($this));
    }

    /**
     * Bulk process multiple transactions.
     */
    public static function bulkProcess(array $ids, string $status, ?string $notes = null, User $processor): void
    {
        $transactions = self::whereIn('id', $ids)
            ->where('status', 'pending')
            ->get();

        foreach ($transactions as $transaction) {
            $transaction->process($status, $notes, $processor);
        }
    }

    /**
     * Bulk tag multiple transactions.
     */
    public static function bulkTag(array $ids, TransactionTag $tag): void
    {
        $transactions = self::whereIn('id', $ids)->get();

        foreach ($transactions as $transaction) {
            $transaction->addTag($tag);
            $transaction->logAudit('tag_added', [
                'tag_id' => $tag->id,
                'tag_name' => $tag->name,
            ]);
        }
    }

    /**
     * Bulk categorize multiple transactions.
     */
    public static function bulkCategorize(array $ids, TransactionCategory $category): void
    {
        $transactions = self::whereIn('id', $ids)->get();

        foreach ($transactions as $transaction) {
            $oldCategory = $transaction->category_id;
            $transaction->update(['category_id' => $category->id]);
            $transaction->logAudit('category_changed', [
                'old_category_id' => $oldCategory,
                'new_category_id' => $category->id,
            ]);
        }
    }

    /**
     * Bulk verify multiple transactions.
     */
    public static function bulkVerify(array $ids, string $status, ?string $notes = null, User $verifier): void
    {
        $transactions = self::whereIn('id', $ids)
            ->where('status', self::STATUS_REQUIRES_VERIFICATION)
            ->get();

        foreach ($transactions as $transaction) {
            $transaction->verify($status, $notes, $verifier);
        }
    }

    /**
     * Get all transaction types for dropdowns
     */
    public static function getTransactionTypes(): array
    {
        return [
            self::TYPE_PAYMENT => 'Member Payment',
            self::TYPE_EXPENSE => 'Expense',
            self::TYPE_INCOME => 'Income',
            self::TYPE_INVESTMENT => 'Investment',
            self::TYPE_BANK_CHARGE => 'Bank Charge',
            self::TYPE_TRANSFER => 'Transfer',
            self::TYPE_REFUND => 'Refund',
        ];
    }

    /**
     * Get all transaction statuses for dropdowns
     */
    public static function getTransactionStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_REQUIRES_VERIFICATION => 'Requires Verification',
            self::STATUS_VERIFIED => 'Verified',
        ];
    }
}
