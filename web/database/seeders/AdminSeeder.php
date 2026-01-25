<?php

declare(strict_types = 1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@doafarma.com.br'],
            [
                'name'              => 'Administrador',
                'email'             => 'admin@doafarma.com.br',
                'phone_number'      => '11999999999',
                'role'              => UserRole::Admin,
                'status'            => UserStatus::Approved,
                'password'          => Hash::make('admin123'),
                'terms_accepted'    => true,
                'terms_accepted_at' => now(),
                'email_verified_at' => now(),
            ]
        );
    }
}
