<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default roles
        $roles = [
            [
                'name' => 'super_admin',
                'display_name' => 'Super Administrator',
                'description' => 'Full access to all system features',
                'is_active' => true,
            ],
            [
                'name' => 'admin',
                'display_name' => 'Administrator',
                'description' => 'Administrative access to most features',
                'is_active' => true,
            ],
            [
                'name' => 'manager',
                'display_name' => 'Manager',
                'description' => 'Limited administrative access',
                'is_active' => true,
            ],
            [
                'name' => 'editor',
                'display_name' => 'Editor',
                'description' => 'Content management access',
                'is_active' => true,
            ],
            [
                'name' => 'user',
                'display_name' => 'User',
                'description' => 'Standard user access',
                'is_active' => true,
            ],
        ];

        foreach ($roles as $roleData) {
            Role::create($roleData);
        }

        // Create common permissions
        $permissions = [
            // User Management
            ['name' => 'view_users', 'display_name' => 'View Users', 'description' => 'Can view user list', 'module' => 'users'],
            ['name' => 'create_users', 'display_name' => 'Create Users', 'description' => 'Can create new users', 'module' => 'users'],
            ['name' => 'edit_users', 'display_name' => 'Edit Users', 'description' => 'Can edit user information', 'module' => 'users'],
            ['name' => 'delete_users', 'display_name' => 'Delete Users', 'description' => 'Can delete users', 'module' => 'users'],

            // Product Management
            ['name' => 'view_products', 'display_name' => 'View Products', 'description' => 'Can view products', 'module' => 'products'],
            ['name' => 'create_products', 'display_name' => 'Create Products', 'description' => 'Can create new products', 'module' => 'products'],
            ['name' => 'edit_products', 'display_name' => 'Edit Products', 'description' => 'Can edit products', 'module' => 'products'],
            ['name' => 'delete_products', 'display_name' => 'Delete Products', 'description' => 'Can delete products', 'module' => 'products'],

            // Event Management
            ['name' => 'view_events', 'display_name' => 'View Events', 'description' => 'Can view events', 'module' => 'events'],
            ['name' => 'create_events', 'display_name' => 'Create Events', 'description' => 'Can create new events', 'module' => 'events'],
            ['name' => 'edit_events', 'display_name' => 'Edit Events', 'description' => 'Can edit events', 'module' => 'events'],
            ['name' => 'delete_events', 'display_name' => 'Delete Events', 'description' => 'Can delete events', 'module' => 'events'],

            // News Management
            ['name' => 'view_news', 'display_name' => 'View News', 'description' => 'Can view news', 'module' => 'news'],
            ['name' => 'create_news', 'display_name' => 'Create News', 'description' => 'Can create new news', 'module' => 'news'],
            ['name' => 'edit_news', 'display_name' => 'Edit News', 'description' => 'Can edit news', 'module' => 'news'],
            ['name' => 'delete_news', 'display_name' => 'Delete News', 'description' => 'Can delete news', 'module' => 'news'],

            // Donation Management
            ['name' => 'view_donations', 'display_name' => 'View Donations', 'description' => 'Can view donations', 'module' => 'donations'],
            ['name' => 'create_donations', 'display_name' => 'Create Donations', 'description' => 'Can create new donations', 'module' => 'donations'],
            ['name' => 'edit_donations', 'display_name' => 'Edit Donations', 'description' => 'Can edit donations', 'module' => 'donations'],
            ['name' => 'delete_donations', 'display_name' => 'Delete Donations', 'description' => 'Can delete donations', 'module' => 'donations'],

            // Music Management
            ['name' => 'view_music', 'display_name' => 'View Music', 'description' => 'Can view music', 'module' => 'music'],
            ['name' => 'create_music', 'display_name' => 'Create Music', 'description' => 'Can create new music', 'module' => 'music'],
            ['name' => 'edit_music', 'display_name' => 'Edit Music', 'description' => 'Can edit music', 'module' => 'music'],
            ['name' => 'delete_music', 'display_name' => 'Delete Music', 'description' => 'Can delete music', 'module' => 'music'],

            // Role Management
            ['name' => 'view_roles', 'display_name' => 'View Roles', 'description' => 'Can view roles', 'module' => 'roles'],
            ['name' => 'create_roles', 'display_name' => 'Create Roles', 'description' => 'Can create new roles', 'module' => 'roles'],
            ['name' => 'edit_roles', 'display_name' => 'Edit Roles', 'description' => 'Can edit roles', 'module' => 'roles'],
            ['name' => 'delete_roles', 'display_name' => 'Delete Roles', 'description' => 'Can delete roles', 'module' => 'roles'],

            // Permission Management
            ['name' => 'view_permissions', 'display_name' => 'View Permissions', 'description' => 'Can view permissions', 'module' => 'permissions'],
            ['name' => 'create_permissions', 'display_name' => 'Create Permissions', 'description' => 'Can create new permissions', 'module' => 'permissions'],
            ['name' => 'edit_permissions', 'display_name' => 'Edit Permissions', 'description' => 'Can edit permissions', 'module' => 'permissions'],
            ['name' => 'delete_permissions', 'display_name' => 'Delete Permissions', 'description' => 'Can delete permissions', 'module' => 'permissions'],
        ];

        foreach ($permissions as $permissionData) {
            Permission::create($permissionData);
        }

        // Assign permissions to roles
        $superAdmin = Role::where('name', 'super_admin')->first();
        $admin = Role::where('name', 'admin')->first();
        $manager = Role::where('name', 'manager')->first();
        $editor = Role::where('name', 'editor')->first();
        $user = Role::where('name', 'user')->first();

        // Super Admin gets all permissions
        $allPermissions = Permission::all();
        $superAdmin->permissions()->sync($allPermissions);

        // Admin gets most permissions
        $adminPermissions = Permission::whereNotIn('name', [
            'create_roles', 'edit_roles', 'delete_roles',
            'create_permissions', 'edit_permissions', 'delete_permissions'
        ])->get();
        $admin->permissions()->sync($adminPermissions);

        // Manager gets limited permissions
        $managerPermissions = Permission::whereIn('module', ['products', 'events', 'news', 'donations', 'music'])
            ->whereNotIn('name', ['delete_products', 'delete_events', 'delete_news', 'delete_donations', 'delete_music'])
            ->get();
        $manager->permissions()->sync($managerPermissions);

        // Editor gets content management permissions
        $editorPermissions = Permission::whereIn('module', ['events', 'news', 'music'])
            ->whereIn('name', ['view_events', 'create_events', 'edit_events', 'view_news', 'create_news', 'edit_news', 'view_music', 'create_music', 'edit_music'])
            ->get();
        $editor->permissions()->sync($editorPermissions);

        // User gets basic viewing permissions
        $userPermissions = Permission::whereIn('name', [
            'view_products', 'view_events', 'view_news', 'view_donations', 'view_music'
        ])->get();
        $user->permissions()->sync($userPermissions);
    }
}
