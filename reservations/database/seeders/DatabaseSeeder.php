<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'email' => 'admin@admin.com',
            'role_id' => Role::ADMINISTRATOR,
        ]);

        $this->call([
            RoleSeeder::class,
        ]);
    }
}
