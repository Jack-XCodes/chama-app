<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

class DocumentType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'allowed_actions',
        'role_permissions',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'allowed_actions' => 'array',
        'role_permissions' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get all available document actions
     */
    public static function availableActions(): array
    {
        return [
            'view' => 'View documents',
            'upload' => 'Upload documents',
            'download' => 'Download documents',
            'delete' => 'Delete documents',
            'share' => 'Share documents',
        ];
    }

    /**
     * Check if a user can perform an action on this document type
     */
    public function canUserPerformAction(User $user, string $action): bool
    {
        // Admin can do everything
        if ($user->hasRole('admin')) {
            return true;
        }

        // Action must be allowed for this document type
        if (!in_array($action, $this->allowed_actions ?? [])) {
            return false;
        }

        // Check role permissions
        $userRoles = $user->roles->pluck('name');
        $rolePermissions = $this->role_permissions ?? [];

        foreach ($userRoles as $role) {
            if (isset($rolePermissions[$role]) && in_array($action, $rolePermissions[$role])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Helper methods for specific actions
     */
    public function canUserView(User $user): bool
    {
        return $this->canUserPerformAction($user, 'view');
    }

    public function canUserUpload(User $user): bool
    {
        return $this->canUserPerformAction($user, 'upload');
    }

    public function canUserDownload(User $user): bool
    {
        // Can't download if can't view
        if (!$this->canUserView($user)) {
            return false;
        }
        return $this->canUserPerformAction($user, 'download');
    }

    public function canUserDelete(User $user): bool
    {
        return $this->canUserPerformAction($user, 'delete');
    }

    public function canUserShare(User $user): bool
    {
        // Can't share if can't view
        if (!$this->canUserView($user)) {
            return false;
        }
        return $this->canUserPerformAction($user, 'share');
    }

    /**
     * Get role permissions as a collection for easier manipulation
     */
    public function getRolePermissionsCollection(): Collection
    {
        return collect($this->role_permissions);
    }

    /**
     * Set role permissions from a collection
     */
    public function setRolePermissionsFromCollection(Collection $permissions): void
    {
        $this->role_permissions = $permissions->toArray();
    }
}
