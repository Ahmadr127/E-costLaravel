<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class SimulationPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            [
                'name' => 'access_simulation',
                'display_name' => 'Akses Simulasi',
                'description' => 'Mengakses halaman simulasi layanan'
            ]
        ];

        foreach ($permissions as $permission) {
            $perm = Permission::updateOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }

        // Attach to admin role if present
        $adminRole = \App\Models\Role::where('name', 'admin')->first();
        if ($adminRole) {
            $perm = Permission::where('name', 'access_simulation')->first();
            if ($perm && !$adminRole->permissions()->where('permission_id', $perm->id)->exists()) {
                $adminRole->permissions()->attach($perm->id);
            }
        }
    }
}

