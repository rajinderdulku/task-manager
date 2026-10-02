<?php

namespace App\Services\Auth;

use App\Data\Auth\LoginData;
use App\Data\Auth\RegisterData;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

final class AuthService
{
    public function register(RegisterData $data): User
    {
        $role = Role::query()
            ->where('name', RoleName::TaskManager->value)
            ->firstOrFail();

        return User::query()->create([
            'role_id' => $role->id,
            'name' => $data->name,
            'email' => $data->email,
            'password' => $data->password,
        ]);
    }

    public function login(LoginData $data): User
    {
        $user = User::query()->where('email', $data->email)->first();

        if ($user === null || ! Hash::check($data->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        return $user;
    }

    public function logout(User $user): void
    {
        $token = $user->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }
    }
}
