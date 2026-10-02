<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::query()
            ->where('name', RoleName::Admin->value)
            ->firstOrFail();

        User::query()->updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@taskmanager.test')],
            [
                'role_id' => $role->id,
                'name' => env('ADMIN_NAME', 'Admin'),
                'password' => env('ADMIN_PASSWORD', 'password'),
            ],
        );
    }
}
