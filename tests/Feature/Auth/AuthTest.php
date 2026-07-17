<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Domains\Auth\Enums\AccountType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('user.name', 'Test User')
            ->assertJsonPath('user.email', 'test@example.com')
            ->assertJsonPath('user.account_type', AccountType::Candidate->value)
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email', 'account_type'],
                'token',
            ]);

        $user = User::where('email', 'test@example.com')->firstOrFail();

        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertSame(AccountType::Candidate, $user->account_type);
        $this->assertDatabaseHas('candidate_profiles', [
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_registration_does_not_allow_clients_to_choose_an_account_type(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'HR User',
            'email' => 'hr@example.com',
            'password' => 'password123',
            'account_type' => AccountType::Hr->value,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('account_type');

        $this->assertDatabaseMissing('users', [
            'email' => 'hr@example.com',
        ]);
    }

    public function test_register_requires_valid_data(): void
    {
        User::factory()->create([
            'email' => 'taken@example.com',
        ]);

        $response = $this->postJson('/api/auth/register', [
            'name' => '',
            'email' => 'taken@example.com',
            'password' => 'short',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_user_can_login(): void
    {
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'login@example.com',
            'password' => 'password123',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.email', 'login@example.com')
            ->assertJsonPath('user.account_type', AccountType::Candidate->value)
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email', 'account_type'],
                'token',
            ]);

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'login@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'login@example.com',
            'password' => 'wrong-password',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/me')->assertUnauthorized();
    }

    public function test_authenticated_user_can_get_their_profile(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonPath('data.account_type', AccountType::Candidate->value)
            ->assertJsonMissingPath('password');
    }

    public function test_logout_deletes_current_access_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->withToken($token)
            ->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Logged out successfully.');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_logout_requires_authentication(): void
    {
        $this->postJson('/api/auth/logout')->assertUnauthorized();
    }
}
