<?php

namespace Tests\Feature\Auth;

use App\Enums\RoleName;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_register_creates_a_task_manager_and_returns_a_token(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Asha Singh',
            'email' => 'asha@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => RoleName::Admin->value,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Asha Singh')
            ->assertJsonPath('data.email', 'asha@example.com')
            ->assertJsonPath('data.role', RoleName::TaskManager->value)
            ->assertJsonStructure(['data' => ['id', 'name', 'email', 'role'], 'token']);

        $response->assertJsonMissingPath('data.password');

        $this->assertDatabaseHas('users', [
            'email' => 'asha@example.com',
        ]);

        $user = User::query()->where('email', 'asha@example.com')->first();

        $this->assertTrue($user?->isTaskManager());
        $this->assertNotNull($response->json('token'));
    }

    public function test_register_rejects_invalid_payloads(): void
    {
        $this->postJson('/api/v1/register', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_register_rejects_a_duplicate_email(): void
    {
        User::factory()->create(['email' => 'asha@example.com']);

        $this->postJson('/api/v1/register', [
            'name' => 'Asha Singh',
            'email' => 'asha@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_returns_a_token_for_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'asha@example.com',
            'password' => 'password',
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'asha@example.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.role', RoleName::TaskManager->value)
            ->assertJsonStructure(['token']);
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'asha@example.com',
            'password' => 'password',
        ]);

        $this->postJson('/api/v1/login', [
            'email' => 'asha@example.com',
            'password' => 'wrong-password',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_current_user_requires_a_token(): void
    {
        $this->getJson('/api/v1/user')->assertUnauthorized();
    }

    public function test_current_user_returns_the_authenticated_user(): void
    {
        $user = User::factory()->admin()->create();
        $token = $user->createToken('api')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/user')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.role', RoleName::Admin->value);
    }

    public function test_logout_revokes_the_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/logout')
            ->assertNoContent();

        $this->assertSame(0, PersonalAccessToken::query()->count());

        $this->app['auth']->forgetGuards();

        $this->withToken($token)
            ->getJson('/api/v1/user')
            ->assertUnauthorized();
    }
}
