<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'document_type_id',
        'uploaded_by',
        'title',
        'description',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'metadata',
        'status',
    ];

    protected $casts = [
        'metadata' => 'array',
        'file_size' => 'integer',
        'last_accessed_at' => 'datetime',
    ];

    /**
     * Get the document type that owns the document.
     */
    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    /**
     * Get the user who uploaded the document.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Check if a user can access this document
     */
    public function canBeAccessedBy(User $user): bool
    {
        return $this->documentType->canUserView($user);
    }

    /**
     * Check if a user can download this document
     */
    public function canBeDownloadedBy(User $user): bool
    {
        return $this->documentType->canUserDownload($user);
    }

    /**
     * Get secure download URL
     */
    public function getSecureUrl(): string
    {
        // This will be handled by a secure route that checks permissions
        return route('documents.download', $this);
    }

    /**
     * Record access
     */
    public function recordAccess(User $user): void
    {
        $this->update([
            'last_accessed_at' => now(),
            'last_accessed_by' => $user->name,
        ]);
    }

    /**
     * Get human readable file size
     */
    public function getHumanFileSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Check if file is previewable
     */
    public function isPreviewable(): bool
    {
        $previewableMimes = [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            'application/pdf',
            'text/plain',
        ];

        return in_array($this->mime_type, $previewableMimes);
    }

    /**
     * Delete the file when model is deleted
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($document) {
            if ($document->isForceDeleting()) {
                Storage::delete($document->file_path);
            }
        });
    }
}
