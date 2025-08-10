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
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'audit_trail' => 'array',
        'processed_at' => 'datetime',
    ];

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
     * Check if transaction can be processed
     */
    public function canBeProcessed(): bool
    {
        return $this->status === 'pending';
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

        $this->update([
            'status' => $status,
            'treasurer_notes' => $notes,
            'processed_by' => $processor->id,
            'processed_at' => now(),
        ]);

        // Log the status change
        $this->logAudit('status_changed', [
            'old_status' => $oldStatus,
            'new_status' => $status,
            'old_notes' => $oldNotes,
            'new_notes' => $notes,
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
}
