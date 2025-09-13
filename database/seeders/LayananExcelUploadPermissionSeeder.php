<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;

class LayananExcelUploadPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Excel upload permissions
        $permissions = [
            [
                'name' => 'upload_layanan_excel',
                'display_name' => 'Upload Excel Layanan',
                'description' => 'Mengizinkan pengguna untuk mengupload file Excel dan mengimpor data layanan'
            ]
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }

        // Assign permission to admin role
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $uploadPermission = Permission::where('name', 'upload_layanan_excel')->first();
            if ($uploadPermission && !$adminRole->permissions()->where('permission_id', $uploadPermission->id)->exists()) {
                $adminRole->permissions()->attach($uploadPermission->id);
            }
        }

        // Assign permission to super_admin role if exists
        $superAdminRole = Role::where('name', 'super_admin')->first();
        if ($superAdminRole) {
            $uploadPermission = Permission::where('name', 'upload_layanan_excel')->first();
            if ($uploadPermission && !$superAdminRole->permissions()->where('permission_id', $uploadPermission->id)->exists()) {
                $superAdminRole->permissions()->attach($uploadPermission->id);
            }
        }

        $this->command->info('Excel upload permissions created and assigned to admin roles successfully!');
    }
}