<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@realestate3d.com'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('admin1234'),
                'role' => 'superadmin',
            ]
        );
    }
}
