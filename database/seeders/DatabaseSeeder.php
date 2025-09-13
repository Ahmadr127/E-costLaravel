<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Call all seeders
        $this->call([
            RolePermissionSeeder::class,
            KategoriSeeder::class,
            LayananExcelUploadPermissionSeeder::class,
        ]);

        // Create admin user
        $adminRole = \App\Models\Role::where('name', 'admin')->first();
        
        // Membuat user admin dengan password
        User::factory()->create([
            'name' => 'Administrator',
            'username' => 'admin',
            'email' => 'admin@example.com',
            'role_id' => $adminRole->id,
            'password' => bcrypt('password'), // tambahkan password default
        ]);
    }
}
