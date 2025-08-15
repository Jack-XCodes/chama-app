<?php

namespace App\Livewire\Admin;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Livewire\Attributes\Rule;
use Spatie\Permission\Models\Role;

class AnnouncementManager extends Component
{
    use WithFileUploads, WithPagination;

    // Form fields
    #[Rule('required|string|min:3|max:255')]
    public $title = '';

    #[Rule('required|string|min:10')]
    public $content = '';

    #[Rule('required|in:low,normal,high,urgent')]
    public $priority = 'normal';

    public $is_urgent = false;

    #[Rule('nullable|date|after:now')]
    public $expires_at = '';

    public $send_email = true;
    public $send_in_app = true;

    public $target_roles = [];
    public $target_users = [];

    #[Rule('nullable|array|max:5')]
    #[Rule('attachments.*|file|max:10240')] // 10MB max per file
    public $attachments = [];

    // Component state
    public $editing = false;
    public $editingAnnouncement = null;
    public $showCreateForm = false;
    public $showDeleteModal = false;
    public $deletingAnnouncement = null;

    // Filters
    public $search = '';
    public $filterStatus = '';
    public $filterPriority = '';
    public $showFilters = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'filterStatus' => ['except' => ''],
        'filterPriority' => ['except' => ''],
    ];

    public function mount()
    {
        $this->authorize('create', Announcement::class);
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function showCreateForm()
    {
        $this->resetForm();
        $this->showCreateForm = true;
    }

    public function hideCreateForm()
    {
        $this->showCreateForm = false;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->reset([
            'title', 'content', 'priority', 'is_urgent', 'expires_at',
            'send_email', 'send_in_app', 'target_roles', 'target_users', 'attachments'
        ]);
        $this->editing = false;
        $this->editingAnnouncement = null;
    }

    public function create()
    {
        $this->authorize('create', Announcement::class);
        $this->validate();

        $attachmentData = $this->processAttachments();

        $announcement = Announcement::create([
            'title' => $this->title,
            'content' => $this->content,
            'priority' => $this->priority,
            'is_urgent' => $this->is_urgent,
            'expires_at' => $this->expires_at ?: null,
            'send_email' => $this->send_email,
            'send_in_app' => $this->send_in_app,
            'target_roles' => empty($this->target_roles) ? null : $this->target_roles,
            'target_users' => empty($this->target_users) ? null : $this->target_users,
            'attachments' => $attachmentData,
            'created_by' => Auth::id(),
        ]);

        session()->flash('message', 'Announcement created successfully!');
        $this->hideCreateForm();
    }

    public function edit(Announcement $announcement)
    {
        $this->authorize('update', $announcement);
        
        $this->editing = true;
        $this->editingAnnouncement = $announcement;
        $this->title = $announcement->title;
        $this->content = $announcement->content;
        $this->priority = $announcement->priority;
        $this->is_urgent = $announcement->is_urgent;
        $this->expires_at = $announcement->expires_at?->format('Y-m-d\TH:i');
        $this->send_email = $announcement->send_email;
        $this->send_in_app = $announcement->send_in_app;
        $this->target_roles = $announcement->target_roles ?? [];
        $this->target_users = $announcement->target_users ?? [];
        
        $this->showCreateForm = true;
    }

    public function update()
    {
        $this->authorize('update', $this->editingAnnouncement);
        $this->validate();

        $attachmentData = $this->processAttachments();
        
        // Merge with existing attachments if any
        if (!empty($this->editingAnnouncement->attachments) && empty($attachmentData)) {
            $attachmentData = $this->editingAnnouncement->attachments;
        }

        $this->editingAnnouncement->update([
            'title' => $this->title,
            'content' => $this->content,
            'priority' => $this->priority,
            'is_urgent' => $this->is_urgent,
            'expires_at' => $this->expires_at ?: null,
            'send_email' => $this->send_email,
            'send_in_app' => $this->send_in_app,
            'target_roles' => empty($this->target_roles) ? null : $this->target_roles,
            'target_users' => empty($this->target_users) ? null : $this->target_users,
            'attachments' => $attachmentData,
        ]);

        session()->flash('message', 'Announcement updated successfully!');
        $this->hideCreateForm();
    }

    public function publish(Announcement $announcement)
    {
        $this->authorize('update', $announcement);
        
        $announcement->publish();
        
        session()->flash('message', 'Announcement published and notifications sent!');
    }

    public function unpublish(Announcement $announcement)
    {
        $this->authorize('update', $announcement);
        
        $announcement->unpublish();
        
        session()->flash('message', 'Announcement unpublished.');
    }

    public function confirmDelete(Announcement $announcement)
    {
        $this->authorize('delete', $announcement);
        
        $this->deletingAnnouncement = $announcement;
        $this->showDeleteModal = true;
    }

    public function delete()
    {
        if ($this->deletingAnnouncement) {
            $this->authorize('delete', $this->deletingAnnouncement);
            
            $this->deletingAnnouncement->delete();
            
            session()->flash('message', 'Announcement deleted successfully.');
            $this->showDeleteModal = false;
            $this->deletingAnnouncement = null;
        }
    }

    public function removeAttachment(int $index)
    {
        if (isset($this->attachments[$index])) {
            unset($this->attachments[$index]);
            $this->attachments = array_values($this->attachments); // Re-index array
        }
    }

    public function clearFilters()
    {
        $this->reset(['search', 'filterStatus', 'filterPriority']);
        $this->resetPage();
    }

    private function processAttachments(): array
    {
        if (empty($this->attachments)) {
            return [];
        }

        $attachmentData = [];

        foreach ($this->attachments as $attachment) {
            $path = $attachment->store('announcements', 'public');
            
            $attachmentData[] = [
                'name' => $attachment->getClientOriginalName(),
                'path' => $path,
                'size' => $attachment->getSize(),
                'type' => $attachment->getMimeType(),
                'uploaded_at' => now()->toISOString(),
            ];
        }

        return $attachmentData;
    }

    private function getFilteredAnnouncements()
    {
        $query = Announcement::with(['creator'])
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('title', 'like', '%' . $this->search . '%')
                          ->orWhere('content', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->filterStatus, function ($query) {
                switch ($this->filterStatus) {
                    case 'published':
                        $query->where('is_published', true);
                        break;
                    case 'unpublished':
                        $query->where('is_published', false);
                        break;
                    case 'expired':
                        $query->where('expires_at', '<', now());
                        break;
                    case 'active':
                        $query->where('is_published', true)
                              ->where(function ($query) {
                                  $query->whereNull('expires_at')
                                        ->orWhere('expires_at', '>', now());
                              });
                        break;
                }
            })
            ->when($this->filterPriority, function ($query) {
                $query->where('priority', $this->filterPriority);
            });

        return $query->orderBy('created_at', 'desc');
    }

    public function render()
    {
        return view('livewire.admin.announcement-manager', [
            'announcements' => $this->getFilteredAnnouncements()->paginate(10),
            'roles' => Role::all(),
            'users' => User::orderBy('name')->get(),
            'priorityLevels' => Announcement::getPriorityLevels(),
        ]);
    }
}