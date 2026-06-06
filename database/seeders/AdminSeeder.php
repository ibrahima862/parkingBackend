<?php

namespace Database\Seeders;

use App\Models\User;
use Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
  public function run(): void
{
    User::create([
        'name' => 'Admin SenovaPark',
        'email' => 'admin@senovapark.sn',
        'telephone' => '770000000',
        'password' => Hash::make('samayaye'),
        'role' => 'admin',
        'is_approved' => true,
    ]);
}
}
