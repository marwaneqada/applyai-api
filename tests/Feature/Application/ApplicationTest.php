<?php

declare(strict_types=1);

namespace Tests\Feature\Application;

use App\Domains\Analysis\Enums\AnalysisStatus;
use App\Domains\Analysis\Models\Analysis;
use App\Domains\Application\Enums\ApplicationStatus;
use App\Domains\Application\Models\Application;
use App\Domains\Resume\Models\Resume;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_application_card(): void
    {
        $user = User::factory()->create();
        $analysis = $this->createAnalysis($user);

        $this->withToken($user->createToken('api-token')->plainTextToken)
            ->postJson('/api/applications', $this->applicationPayload([
                'analysis_id' => $analysis->id,
            ]))
            ->assertCreated()
            ->assertJsonPath('data.analysis_id', $analysis->id)
            ->assertJsonPath('data.status', ApplicationStatus::Saved->value)
            ->assertJsonPath('data.position', 1);

        $this->assertDatabaseHas('applications', [
            'user_id' => $user->id,
            'analysis_id' => $analysis->id,
            'company_name' => 'Tech Company',
            'status' => ApplicationStatus::Saved->value,
        ]);
    }

    public function test_create_application_requires_authentication(): void
    {
        $this->postJson('/api/applications', $this->applicationPayload())
            ->assertUnauthorized();
    }

    public function test_create_application_requires_valid_data(): void
    {
        $user = User::factory()->create();

        $this->withToken($user->createToken('api-token')->plainTextToken)
            ->postJson('/api/applications', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['company_name', 'job_title']);

        $this->assertDatabaseCount('applications', 0);
    }

    public function test_user_cannot_create_application_for_another_users_analysis(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $analysis = $this->createAnalysis($otherUser);

        $this->withToken($user->createToken('api-token')->plainTextToken)
            ->postJson('/api/applications', $this->applicationPayload([
                'analysis_id' => $analysis->id,
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['analysis_id']);

        $this->assertDatabaseCount('applications', 0);
    }

    public function test_user_sees_applications_grouped_by_status(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $saved = $this->createApplicationCard($user, [
            'status' => ApplicationStatus::Saved,
            'position' => 2.0,
        ]);
        $interview = $this->createApplicationCard($user, [
            'status' => ApplicationStatus::Interview,
            'position' => 1.0,
            'company_name' => 'Interview Co',
        ]);
        $this->createApplicationCard($otherUser);

        $this->withToken($user->createToken('api-token')->plainTextToken)
            ->getJson('/api/applications')
            ->assertOk()
            ->assertJsonPath('data.saved.0.id', $saved->id)
            ->assertJsonPath('data.interview.0.id', $interview->id)
            ->assertJsonCount(1, 'data.saved')
            ->assertJsonCount(0, 'data.offer');
    }

    public function test_user_can_view_their_application(): void
    {
        $user = User::factory()->create();
        $application = $this->createApplicationCard($user);

        $this->withToken($user->createToken('api-token')->plainTextToken)
            ->getJson("/api/applications/{$application->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $application->id)
            ->assertJsonPath('data.company_name', 'Tech Company');
    }

    public function test_user_cannot_view_another_users_application(): void
    {
        $user = User::factory()->create();
        $application = $this->createApplicationCard(User::factory()->create());

        $this->withToken($user->createToken('api-token')->plainTextToken)
            ->getJson("/api/applications/{$application->id}")
            ->assertForbidden();
    }

    public function test_user_can_update_application_fields(): void
    {
        $user = User::factory()->create();
        $application = $this->createApplicationCard($user);

        $this->withToken($user->createToken('api-token')->plainTextToken)
            ->putJson("/api/applications/{$application->id}", [
                'company_name' => 'Updated Company',
                'notes' => 'Follow up next week.',
                'contact_email' => 'recruiter@example.com',
            ])
            ->assertOk()
            ->assertJsonPath('data.company_name', 'Updated Company')
            ->assertJsonPath('data.notes', 'Follow up next week.');

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'company_name' => 'Updated Company',
            'contact_email' => 'recruiter@example.com',
        ]);
    }

    public function test_user_cannot_update_another_users_application(): void
    {
        $user = User::factory()->create();
        $application = $this->createApplicationCard(User::factory()->create());

        $this->withToken($user->createToken('api-token')->plainTextToken)
            ->putJson("/api/applications/{$application->id}", [
                'company_name' => 'Updated Company',
            ])
            ->assertForbidden();
    }

    public function test_user_can_move_application_between_columns_and_reorder(): void
    {
        $user = User::factory()->create();
        $application = $this->createApplicationCard($user);
        $after = $this->createApplicationCard($user, [
            'status' => ApplicationStatus::Interview,
            'position' => 1.0,
        ]);
        $before = $this->createApplicationCard($user, [
            'status' => ApplicationStatus::Interview,
            'position' => 2.0,
        ]);

        $this->withToken($user->createToken('api-token')->plainTextToken)
            ->patchJson("/api/applications/{$application->id}/move", [
                'status' => ApplicationStatus::Interview->value,
                'after_application_id' => $after->id,
                'before_application_id' => $before->id,
            ])
            ->assertOk()
            ->assertJsonPath('data.status', ApplicationStatus::Interview->value)
            ->assertJsonPath('data.position', 1.5);

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'status' => ApplicationStatus::Interview->value,
            'position' => 1.5,
        ]);
    }

    public function test_moving_application_to_its_current_status_without_neighbors_keeps_position(): void
    {
        $user = User::factory()->create();
        $application = $this->createApplicationCard($user, [
            'status' => ApplicationStatus::Interview,
            'position' => 3.0,
        ]);

        $this->withToken($user->createToken('api-token')->plainTextToken)
            ->patchJson("/api/applications/{$application->id}/move", [
                'status' => ApplicationStatus::Interview->value,
            ])
            ->assertOk()
            ->assertJsonPath('data.status', ApplicationStatus::Interview->value)
            ->assertJsonPath('data.position', 3);

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'status' => ApplicationStatus::Interview->value,
            'position' => 3.0,
        ]);
    }

    public function test_user_cannot_move_application_relative_to_another_users_card(): void
    {
        $user = User::factory()->create();
        $application = $this->createApplicationCard($user);
        $otherApplication = $this->createApplicationCard(User::factory()->create(), [
            'status' => ApplicationStatus::Interview,
        ]);

        $this->withToken($user->createToken('api-token')->plainTextToken)
            ->patchJson("/api/applications/{$application->id}/move", [
                'status' => ApplicationStatus::Interview->value,
                'after_application_id' => $otherApplication->id,
            ])
            ->assertForbidden();
    }

    public function test_user_cannot_move_application_relative_to_card_in_another_status(): void
    {
        $user = User::factory()->create();
        $application = $this->createApplicationCard($user);
        $before = $this->createApplicationCard($user, [
            'status' => ApplicationStatus::Interview,
        ]);

        $this->withToken($user->createToken('api-token')->plainTextToken)
            ->patchJson("/api/applications/{$application->id}/move", [
                'status' => ApplicationStatus::Saved->value,
                'before_application_id' => $before->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['before_application_id'])
            ->assertJsonPath(
                'errors.before_application_id.0',
                'The selected before_application_id must belong to the saved column.'
            );
    }

    public function test_user_can_delete_their_application(): void
    {
        $user = User::factory()->create();
        $application = $this->createApplicationCard($user);

        $this->withToken($user->createToken('api-token')->plainTextToken)
            ->deleteJson("/api/applications/{$application->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('applications', [
            'id' => $application->id,
        ]);
    }

    public function test_user_cannot_delete_another_users_application(): void
    {
        $user = User::factory()->create();
        $application = $this->createApplicationCard(User::factory()->create());

        $this->withToken($user->createToken('api-token')->plainTextToken)
            ->deleteJson("/api/applications/{$application->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
        ]);
    }

    public function test_user_can_view_application_stats(): void
    {
        $user = User::factory()->create();

        $this->createApplicationCard($user, ['status' => ApplicationStatus::Saved]);
        $this->createApplicationCard($user, ['status' => ApplicationStatus::Applied]);
        $this->createApplicationCard($user, ['status' => ApplicationStatus::Rejected]);
        $this->createApplicationCard(User::factory()->create(), ['status' => ApplicationStatus::Offer]);

        $this->withToken($user->createToken('api-token')->plainTextToken)
            ->getJson('/api/applications/stats')
            ->assertOk()
            ->assertJsonPath('data.total', 3)
            ->assertJsonPath('data.active', 2)
            ->assertJsonPath('data.by_status.saved', 1)
            ->assertJsonPath('data.by_status.offer', 0);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function applicationPayload(array $overrides = []): array
    {
        return array_merge([
            'company_name' => 'Tech Company',
            'job_title' => 'Backend Developer',
            'job_url' => 'https://example.com/jobs/backend-developer',
            'applied_date' => '2026-05-10',
            'contact_name' => 'Jane Recruiter',
            'contact_email' => 'jane@example.com',
            'notes' => 'Promising role.',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createApplicationCard(User $user, array $overrides = []): Application
    {
        $status = $overrides['status'] ?? ApplicationStatus::Saved;
        unset($overrides['status']);

        return Application::create(array_merge($this->applicationPayload(), [
            'user_id' => $user->id,
            'status' => $status instanceof ApplicationStatus ? $status->value : $status,
            'position' => 1.0,
        ], $overrides));
    }

    private function createAnalysis(User $user): Analysis
    {
        $resume = Resume::create([
            'user_id' => $user->id,
            'original_filename' => 'resume.pdf',
            'stored_path' => 'resumes/resume.pdf',
            'file_size' => 100,
            'mime_type' => 'application/pdf',
            'parse_status' => 'success',
            'extracted_text' => str_repeat('Experienced developer. ', 10),
        ]);

        return Analysis::create([
            'user_id' => $user->id,
            'resume_id' => $resume->id,
            'job_title' => 'Backend Developer',
            'company_name' => 'Tech Company',
            'job_url' => 'https://example.com/jobs/backend-developer',
            'job_description' => str_repeat('Build APIs and services. ', 6),
            'status' => AnalysisStatus::Completed,
        ]);
    }
}
