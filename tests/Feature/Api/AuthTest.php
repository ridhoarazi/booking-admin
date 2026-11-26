<?php

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_with_default_role(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Budi',
            'email' => 'budi@example.test',
            'phone' => '08123456789',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.user.email', 'budi@example.test')
            ->assertJsonPath('data.user.role', UserRole::User->value)
            ->assertJsonStructure(['data' => ['user' => ['id', 'name', 'email', 'role'], 'token']]);

        $this->assertDatabaseHas('users', [
            'email' => 'budi@example.test',
            'role' => UserRole::User->value,
        ]);
    }

    public function test_register_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'budi@example.test']);

        $this->postJson('/api/v1/register', [
            'name' => 'Budi',
            'email' => 'budi@example.test',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_register_never_creates_admin(): void
    {
        $this->postJson('/api/v1/register', [
            'name' => 'Budi',
            'email' => 'budi@example.test',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'role' => 'admin',
        ])->assertCreated();

        $this->assertSame(UserRole::User, User::where('email', 'budi@example.test')->first()->role);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'budi@example.test',
            'password' => Hash::make('secret123'),
        ]);

        $this->postJson('/api/v1/login', [
            'email' => 'budi@example.test',
            'password' => 'secret123',
        ])->assertOk()
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonStructure(['data' => ['token']]);

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'budi@example.test',
            'password' => Hash::make('secret123'),
        ]);

        $this->postJson('/api/v1/login', [
            'email' => 'budi@example.test',
            'password' => 'salah',
        ])->assertStatus(401)
            ->assertJsonPath('message', 'Email atau password salah.');
    }

    public function test_authenticated_user_can_fetch_profile(): void
    {
        $user = User::factory()->create(['role' => UserRole::User]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/user')
            ->assertOk()
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.role', UserRole::User->value);
    }

    public function test_guest_cannot_access_protected_endpoints(): void
    {
        $this->getJson('/api/v1/user')->assertStatus(401);
        $this->postJson('/api/v1/logout')->assertStatus(401);
    }

    public function test_logout_revokes_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth-token')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/logout')
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_admin_middleware_blocks_non_admin(): void
    {
        Route::middleware(['auth:sanctum', 'admin'])->get('/_test/admin-area', fn () => response('ok'));

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $user = User::factory()->create(['role' => UserRole::User]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/_test/admin-area')
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->getJson('/_test/admin-area')
            ->assertForbidden();
    }

    public function test_register_is_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/v1/register', [
                'name' => 'User '.$attempt,
                'email' => 'user'.$attempt.'@example.test',
                'password' => 'secret123',
                'password_confirmation' => 'secret123',
            ])->assertCreated();
        }

        $this->postJson('/api/v1/register', [
            'name' => 'User Terakhir',
            'email' => 'terakhir@example.test',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertStatus(429)
            ->assertHeader('Retry-After')
            ->assertJsonStructure(['message']);
    }

    public function test_login_is_rate_limited_after_five_failed_attempts(): void
    {
        User::factory()->create([
            'email' => 'budi@example.test',
            'password' => Hash::make('secret123'),
        ]);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/v1/login', [
                'email' => 'budi@example.test',
                'password' => 'salah',
            ])->assertStatus(401);
        }

        $this->postJson('/api/v1/login', [
            'email' => 'budi@example.test',
            'password' => 'secret123',
        ])->assertStatus(429)
            ->assertHeader('Retry-After');
    }

    public function test_login_rate_limit_is_scoped_per_email(): void
    {
        foreach (['satu@example.test', 'dua@example.test'] as $email) {
            User::factory()->create([
                'email' => $email,
                'password' => Hash::make('secret123'),
            ]);
        }

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/v1/login', [
                'email' => 'satu@example.test',
                'password' => 'salah',
            ])->assertStatus(401);
        }

        $this->postJson('/api/v1/login', [
            'email' => 'satu@example.test',
            'password' => 'secret123',
        ])->assertStatus(429);

        $this->postJson('/api/v1/login', [
            'email' => 'dua@example.test',
            'password' => 'secret123',
        ])->assertOk();
    }
}
