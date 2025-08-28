<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransactionTag extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'color',
        'description',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the user who created the tag.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the tag.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the transactions with this tag.
     */
    public function transactions(): BelongsToMany
    {
        return $this->belongsToMany(Transaction::class, 'transaction_tag')
            ->withPivot('tagged_by', 'tagged_at')
            ->using(TransactionTagPivot::class);
    }

    /**
     * Get the total amount for this tag.
     */
    public function getTotalAmount(): float
    {
        return $this->transactions()
            ->whereIn('status', ['approved'])
            ->sum('amount');
    }

    /**
     * Get the count of transactions with this tag.
     */
    public function getTransactionCount(): int
    {
        return $this->transactions()
            ->whereIn('status', ['approved'])
            ->count();
    }

    /**
     * Scope a query to only include active tags.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
