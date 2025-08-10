<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Notifications\PaymentStatusChanged;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
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
        'processed_by',
        'processed_at',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
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

        $this->update([
            'status' => $status,
            'treasurer_notes' => $notes,
            'processed_by' => $processor->id,
            'processed_at' => now(),
        ]);

        // Send notification to the user
        $this->user->notify(new PaymentStatusChanged($this));
    }
}
