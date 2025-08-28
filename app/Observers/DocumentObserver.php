<?php

namespace App\Observers;

use App\Models\Document;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DocumentObserver
{
    /**
     * Handle the Document "created" event.
     */
    public function created(Document $document): void
    {
        Log::info('Document created', [
            'document_id' => $document->id,
            'title' => $document->title,
            'type' => $document->documentType->name,
            'user' => Auth::user()->name,
            'action' => 'created',
        ]);
    }

    /**
     * Handle the Document "updated" event.
     */
    public function updated(Document $document): void
    {
        $changes = $document->getChanges();
        unset($changes['updated_at']); // Don't log timestamp updates
        
        if (!empty($changes)) {
            Log::info('Document updated', [
                'document_id' => $document->id,
                'title' => $document->title,
                'user' => Auth::user()->name,
                'action' => 'updated',
                'changes' => $changes,
            ]);
        }
    }

    /**
     * Handle the Document "deleted" event.
     */
    public function deleted(Document $document): void
    {
        Log::info('Document deleted', [
            'document_id' => $document->id,
            'title' => $document->title,
            'user' => Auth::user()->name,
            'action' => 'deleted',
            'force_delete' => false,
        ]);
    }

    /**
     * Handle the Document "restored" event.
     */
    public function restored(Document $document): void
    {
        Log::info('Document restored', [
            'document_id' => $document->id,
            'title' => $document->title,
            'user' => Auth::user()->name,
            'action' => 'restored',
        ]);
    }

    /**
     * Handle the Document "force deleted" event.
     */
    public function forceDeleted(Document $document): void
    {
        Log::info('Document permanently deleted', [
            'document_id' => $document->id,
            'title' => $document->title,
            'user' => Auth::user()->name,
            'action' => 'force_deleted',
        ]);
    }
}
