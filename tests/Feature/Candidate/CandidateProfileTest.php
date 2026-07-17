<?php

declare(strict_types=1);

namespace Tests\Feature\Candidate;

use App\Domains\Auth\Enums\AccountType;
use App\Domains\Candidate\Models\CandidateProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CandidateProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_candidate_can_view_their_profile(): void
    {
        [$user, $profile, $token] = $this->candidate();

        $this->withToken($token)
            ->getJson('/api/candidate/profile')
            ->assertOk()
            ->assertJsonPath('data.id', $profile->id)
            ->assertJsonPath('data.name', $user->name)
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonPath('data.account_type', AccountType::Candidate->value)
            ->assertJsonPath('data.headline', null);
    }

    public function test_candidate_can_update_their_profile_and_name(): void
    {
        [$user, $profile, $token] = $this->candidate();

        $this->withToken($token)
            ->patchJson('/api/candidate/profile', [
                'name' => 'Updated Candidate',
                'headline' => 'Backend Engineer',
                'phone' => '+212 600 000 000',
                'location' => 'Casablanca, Morocco',
                'professional_summary' => 'Builds reliable APIs.',
                'linkedin_url' => 'https://www.linkedin.com/in/candidate',
                'github_url' => 'https://github.com/candidate',
                'portfolio_url' => 'https://candidate.example.com',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Candidate')
            ->assertJsonPath('data.headline', 'Backend Engineer')
            ->assertJsonPath('data.location', 'Casablanca, Morocco');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Candidate',
        ]);
        $this->assertDatabaseHas('candidate_profiles', [
            'id' => $profile->id,
            'headline' => 'Backend Engineer',
            'portfolio_url' => 'https://candidate.example.com',
        ]);
    }

    public function test_candidate_can_clear_nullable_profile_fields(): void
    {
        [, $profile, $token] = $this->candidate([
            'headline' => 'Backend Engineer',
            'phone' => '+212 600 000 000',
        ]);

        $this->withToken($token)
            ->patchJson('/api/candidate/profile', [
                'headline' => null,
                'phone' => null,
            ])
            ->assertOk()
            ->assertJsonPath('data.headline', null)
            ->assertJsonPath('data.phone', null);

        $this->assertDatabaseHas('candidate_profiles', [
            'id' => $profile->id,
            'headline' => null,
            'phone' => null,
        ]);
    }

    public function test_profile_update_validates_fields(): void
    {
        [, , $token] = $this->candidate();

        $this->withToken($token)
            ->patchJson('/api/candidate/profile', [
                'name' => '',
                'headline' => str_repeat('a', 161),
                'phone' => str_repeat('1', 31),
                'linkedin_url' => 'not-a-url',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'headline', 'phone', 'linkedin_url']);
    }

    public function test_profile_endpoints_require_authentication(): void
    {
        $this->getJson('/api/candidate/profile')->assertUnauthorized();
        $this->patchJson('/api/candidate/profile')->assertUnauthorized();
    }

    public function test_hr_account_cannot_access_candidate_profile_or_product_routes(): void
    {
        $hr = User::factory()->create([
            'account_type' => AccountType::Hr,
        ]);
        $token = $hr->createToken('api-token')->plainTextToken;

        $this->withToken($token)->getJson('/api/candidate/profile')->assertForbidden();
        $this->withToken($token)->patchJson('/api/candidate/profile')->assertForbidden();
        $this->withToken($token)->getJson('/api/resumes')->assertForbidden();
        $this->withToken($token)->getJson('/api/analyses')->assertForbidden();
        $this->withToken($token)->getJson('/api/applications')->assertForbidden();
    }

    public function test_hr_account_can_still_use_shared_authenticated_routes(): void
    {
        $hr = User::factory()->create([
            'account_type' => AccountType::Hr,
        ]);
        $token = $hr->createToken('api-token')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('data.account_type', AccountType::Hr->value);

        $this->withToken($token)
            ->postJson('/api/auth/logout')
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    /**
     * @param  array<string, mixed>  $profileAttributes
     * @return array{User, CandidateProfile, string}
     */
    private function candidate(array $profileAttributes = []): array
    {
        $user = User::factory()->create();
        $profile = $user->candidateProfile()->create($profileAttributes);

        return [$user, $profile, $user->createToken('api-token')->plainTextToken];
    }
}
