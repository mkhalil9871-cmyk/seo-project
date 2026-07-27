<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'manage_users',
            'manage_projects',
            'manage_settings',
            'create_projects',
            'run_audit',
            'generate_strategy',
            'manage_keywords',
            'view_projects',
            'upload_data',
            'view_reports',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $admin = Role::firstOrCreate(['name' => 'Admin']);
        $admin->syncPermissions(['manage_users', 'manage_projects', 'manage_settings']);

        $manager = Role::firstOrCreate(['name' => 'SEO Manager']);
        $manager->syncPermissions(['create_projects', 'run_audit', 'generate_strategy', 'manage_keywords']);

        $analyst = Role::firstOrCreate(['name' => 'SEO Analyst']);
        $analyst->syncPermissions(['view_projects', 'upload_data', 'view_reports']);
    }
}