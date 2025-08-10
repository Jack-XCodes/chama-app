<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DocumentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view documents list
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Document $document): bool
    {
        return $document->canBeAccessedBy($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // User can create if they have upload permission on any document type
        return DocumentType::all()->contains(function ($type) use ($user) {
            return $type->canUserUpload($user);
        });
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Document $document): bool
    {
        // Only admin or the uploader can update
        return $user->hasRole('admin') || $document->uploaded_by === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Document $document): bool
    {
        // Only admin or the uploader can delete
        return $user->hasRole('admin') || $document->uploaded_by === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Document $document): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Document $document): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can download the model.
     */
    public function download(User $user, Document $document): bool
    {
        return $document->canBeDownloadedBy($user);
    }
}
