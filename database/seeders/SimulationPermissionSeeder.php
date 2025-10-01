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
            ],
            [
                'name' => 'access_simulation_qty',
                'display_name' => 'Akses Simulasi Qty',
                'description' => 'Mengakses halaman simulasi layanan berbasis kuantitas'
            ],
            [
                'name' => 'manage_simulation_qty_presets',
                'display_name' => 'Kelola Preset Qty',
                'description' => 'Mengelola master preset qty (global)'
            ],
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
            $perms = Permission::whereIn('name', ['access_simulation', 'access_simulation_qty', 'manage_simulation_qty_presets'])->get();
            foreach ($perms as $perm) {
                if (!$adminRole->permissions()->where('permission_id', $perm->id)->exists()) {
                    $adminRole->permissions()->attach($perm->id);
                }
            }
        }
    }
}

