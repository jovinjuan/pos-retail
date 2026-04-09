<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $email    = env('OWNER_EMAIL', 'owner@pos.local');
        $password = env('OWNER_PASSWORD', 'password');
        $name     = env('OWNER_NAME', 'Owner');

        User::updateOrCreate(
            ['email' => $email],
            [
                'name'     => $name,
                'password' => Hash::make($password),
            ]
        );
    }
}
