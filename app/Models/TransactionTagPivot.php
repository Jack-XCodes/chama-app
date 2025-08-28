<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class TransactionTagPivot extends Pivot
{
    protected $table = 'transaction_tag';

    public $timestamps = false;

    protected $fillable = [
        'transaction_id',
        'transaction_tag_id',
        'tagged_by',
        'tagged_at',
    ];

    protected $casts = [
        'tagged_at' => 'datetime',
    ];

    /**
     * Get the user who tagged the transaction.
     */
    public function tagger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tagged_by');
    }
}
