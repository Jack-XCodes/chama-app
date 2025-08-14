<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed roles and permissions
        $permissions = [
            'manage-users',
            'manage-documents',
            'manage-finances',
            'reconcile-payments',
            'view-reports',
            'send-announcements',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName]);
        }

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $treasurerRole = Role::firstOrCreate(['name' => 'treasurer']);
        $memberRole = Role::firstOrCreate(['name' => 'member']);

        $adminRole->syncPermissions(Permission::all());
        $treasurerRole->syncPermissions(Permission::whereIn('name', [
            'manage-finances',
            'reconcile-payments',
            'view-reports',
            'manage-documents',
        ])->get());
        $memberRole->syncPermissions(Permission::whereIn('name', [
            'view-reports',
        ])->get());

        // Create an initial admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Administrator', 'password' => bcrypt('password')]
        );
        $admin->assignRole($adminRole);

        // Seed transaction categories and tags
        $this->call([
            TransactionCategorySeeder::class,
            TransactionTagSeeder::class,
        ]);
    }
}
