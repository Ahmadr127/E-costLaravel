<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;

class AddLayananPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Add new permissions
        $permissions = [
            ['name' => 'manage_layanan', 'display_name' => 'Kelola Layanan', 'description' => 'Mengelola data layanan'],
            ['name' => 'manage_kategori', 'display_name' => 'Kelola Kategori', 'description' => 'Mengelola kategori layanan'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }

        // Assign permissions to roles
        $adminRole = Role::where('name', 'admin')->first();
        $librarianRole = Role::where('name', 'librarian')->first();

        if ($adminRole) {
            $adminRole->permissions()->syncWithoutDetaching(
                Permission::whereIn('name', ['manage_layanan', 'manage_kategori', 'access_simulation'])->get()
            );
        }


    }
}
