<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@senovapark.sn'], // Vérification d'existence
            [
                'name' => 'Admin SenovaPark',
                'telephone' => '770000000',
                'password' => Hash::make('samayaye'),
                'role' => 'admin',
                'is_approved' => true,
            ]
        );
    }
}
