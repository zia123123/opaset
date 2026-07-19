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
            ['email' => 'admin@bulog.co.id'],
            [
                'name' => 'Admin Opaset',
                'password' => Hash::make('Opaset#Bulog2026!'),
            ]
        );
    }
}