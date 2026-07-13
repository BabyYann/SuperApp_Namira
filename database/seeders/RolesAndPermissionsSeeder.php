<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Yayasan Level Roles (Global)
        $yayasanRoles = [
            'super_admin_yayasan',
            'admin_yayasan',
            'staff_yayasan',
        ];

        foreach ($yayasanRoles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        // 2. Unit Level Roles (Scoped usually, but defined globally for assignment)
        $unitRoles = [
            'kepala_sekolah', // Principal Role
            'admin_unit',
            'staff_unit',
            'teacher',
            'siswa',
            'koordinator_kurikulum', // Helper Role
            'wali_kelas', // Helper Role
            'staff_admin_keuangan', // Finance Role
            'finance', // Alias if needed
            'koordinator_sarpar', // Sarpar Module Role
            'humas_unit', // Public Relations Role
            'bk', // Guidance Counseling Role
        ];

        foreach ($unitRoles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        // 3. Define Basic Permissions (Granular)
        $permissions = [
            // User Management
            'view_users', 'create_users', 'edit_users', 'delete_users',
            // Unit Management
            'view_units', 'create_units', 'edit_units', 'delete_units',
            // Academic
            'view_classes', 'create_classes', 'edit_classes', 'delete_classes',
            'view_students', 'create_students', 'edit_students', 'delete_students',
            'view_teachers', 'create_teachers', 'edit_teachers', 'delete_teachers',
            'view_schedules', 'create_schedules', 'edit_schedules', 'delete_schedules',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Assign super admin
        $superAdmin = Role::findByName('super_admin_yayasan');
        $superAdmin->givePermissionTo(Permission::all());
    }
}
