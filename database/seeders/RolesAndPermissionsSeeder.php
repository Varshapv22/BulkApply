<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    private const PERMISSIONS = [
        'manage-users',
        'manage-jobs',
        'manage-templates',
        'view-reports',
        'manage-queue',
        'manage-settings',
        'manage-api',
        'manage-subscriptions',
        'view-audit-logs',
    ];

    private const ROLE_PERMISSIONS = [
        'super-admin' => self::PERMISSIONS,
        'admin' => [
            'manage-users', 'manage-jobs', 'manage-templates', 'view-reports',
            'manage-queue', 'manage-settings', 'manage-subscriptions', 'view-audit-logs',
        ],
        'support' => ['manage-jobs', 'view-reports'],
        'moderator' => ['manage-jobs', 'manage-templates', 'view-reports'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::PERMISSIONS as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        foreach (self::ROLE_PERMISSIONS as $roleName => $permissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($permissions);
        }

        $ownerEmail = env('ADMIN_SEED_EMAIL', 'admin@gmail.com');
        $owner = User::where('email', $ownerEmail)->first();

        if (!$owner) {
            $password = str()->password(16);

            $owner = User::create([
                'name' => 'Admin',
                'email' => $ownerEmail,
                'password' => $password,
                'email_verified_at' => now(),
            ]);

            $this->command?->warn("Created admin user {$ownerEmail} with password: {$password}");
            $this->command?->warn('Log in and change this password immediately.');
        }

        $owner->assignRole('super-admin');
    }
}
