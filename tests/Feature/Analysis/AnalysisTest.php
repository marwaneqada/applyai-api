<?php

namespace Tests\Feature\Analysis;

use App\Domains\Analysis\Enums\AnalysisStatus;
use App\Domains\Analysis\Jobs\AnalyzeResumeJob;
use App\Domains\Analysis\Models\Analysis;
use App\Domains\Analysis\Models\AnalysisResult;
use App\Domains\Analysis\Services\FakeAnalysisResultGenerator;
use App\Domains\Resume\Models\Resume;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AnalysisTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_analysis_for_parsed_resume(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $resume = Resume::create($this->resumeAttributes($user));

        $this->withToken($user->createToken('api-token')->plainTextToken)
            ->postJson('/api/analyses', $this->analysisPayload($resume))
            ->assertCreated()
            ->assertJsonPath('data.resume_id', $resume->id)
            ->assertJsonPath('data.job_title', 'Laravel Developer')
            ->assertJsonPath('data.status', AnalysisStatus::Pending->value)
            ->assertJsonPath('data.result', null);

        $this->assertDatabaseHas('analyses', [
            'user_id' => $user->id,
            'resume_id' => $resume->id,
            'status' => AnalysisStatus::Pending->value,
        ]);

        $this->assertDatabaseCount('analysis_results', 0);

        Queue::assertPushed(AnalyzeResumeJob::class);
    }

    public function test_create_analysis_requires_authentication(): void
    {
        $this->postJson('/api/analyses', [
            'resume_id' => 1,
        ])->assertUnauthorized();
    }

    public function test_user_cannot_create_analysis_for_another_users_resume(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $resume = Resume::create($this->resumeAttributes($otherUser));

        $this->withToken($user->createToken('api-token')->plainTextToken)
            ->postJson('/api/analyses', $this->analysisPayload($resume))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['resume_id']);

        $this->assertDatabaseCount('analyses', 0);
    }

    public function test_user_cannot_create_analysis_for_unparsed_resume(): void
    {
        $user = User::factory()->create();
        $resume = Resume::create($this->resumeAttributes($user, [
            'parse_status' => 'failed',
        ]));

        $this->withToken($user->createToken('api-token')->plainTextToken)
            ->postJson('/api/analyses', $this->analysisPayload($resume))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['resume_id']);

        $this->assertDatabaseCount('analyses', 0);
    }

    public function test_user_only_sees_their_analyses(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $mine = $this->createAnalysis($user, [
            'job_title' => 'Mine',
        ]);

        $this->createAnalysis($otherUser, [
            'job_title' => 'Theirs',
        ]);

        $this->withToken($user->createToken('api-token')->plainTextToken)
            ->getJson('/api/analyses')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $mine->id)
            ->assertJsonPath('data.0.job_title', 'Mine');
    }

    public function test_user_can_view_their_analysis_with_result(): void
    {
        $user = User::factory()->create();
        $analysis = $this->createAnalysis($user);

        AnalysisResult::create([
            'analysis_id' => $analysis->id,
            'overall_score' => 91,
            'keyword_score' => 90,
            'experience_score' => 92,
            'skills_score' => 93,
            'matched_keywords' => ['Laravel'],
            'missing_keywords' => ['Docker'],
            'strengths' => ['Strong PHP experience.'],
            'weaknesses' => ['Needs more deployment detail.'],
            'gap_analysis' => ['Add Docker examples.'],
            'rewritten_bullets' => ['Built Laravel APIs.'],
            'cover_letter' => 'Dear Hiring Manager...',
            'model_used' => 'test-model',
        ]);

        $this->withToken($user->createToken('api-token')->plainTextToken)
            ->getJson("/api/analyses/{$analysis->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $analysis->id)
            ->assertJsonPath('data.result.overall_score', 91);
    }

    public function test_user_can_view_their_analysis_status(): void
    {
        $user = User::factory()->create();
        $analysis = $this->createAnalysis($user, [
            'status' => AnalysisStatus::Processing,
        ]);

        $this->withToken($user->createToken('api-token')->plainTextToken)
            ->getJson("/api/analyses/{$analysis->id}/status")
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    'status' => AnalysisStatus::Processing->value,
                ],
            ]);
    }

    public function test_user_cannot_view_another_users_analysis(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $analysis = $this->createAnalysis($otherUser);

        $this->withToken($user->createToken('api-token')->plainTextToken)
            ->getJson("/api/analyses/{$analysis->id}")
            ->assertForbidden();
    }

    public function test_user_cannot_view_another_users_analysis_status(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $analysis = $this->createAnalysis($otherUser);

        $this->withToken($user->createToken('api-token')->plainTextToken)
            ->getJson("/api/analyses/{$analysis->id}/status")
            ->assertForbidden();
    }

    public function test_analyze_resume_job_can_be_rerun_without_duplicate_result_failure(): void
    {
        $user = User::factory()->create();
        $analysis = $this->createAnalysis($user);

        (new AnalyzeResumeJob($analysis->id))->handle(app(FakeAnalysisResultGenerator::class));
        (new AnalyzeResumeJob($analysis->id))->handle(app(FakeAnalysisResultGenerator::class));

        $analysis->refresh();

        $this->assertSame(AnalysisStatus::Completed, $analysis->status);
        $this->assertDatabaseCount('analysis_results', 1);
        $this->assertDatabaseHas('analysis_results', [
            'analysis_id' => $analysis->id,
            'overall_score' => 78,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function resumeAttributes(User $user, array $overrides = []): array
    {
        return array_merge([
            'user_id' => $user->id,
            'original_filename' => 'resume.pdf',
            'stored_path' => 'resumes/resume.pdf',
            'file_size' => 100,
            'mime_type' => 'application/pdf',
            'parse_status' => 'success',
            'extracted_text' => str_repeat('Experienced developer. ', 10),
        ], $overrides);
    }

    /**
     * @return array<string, mixed>
     */
    private function analysisPayload(Resume $resume): array
    {
        return [
            'resume_id' => $resume->id,
            'job_title' => 'Laravel Developer',
            'company_name' => 'ApplyAI',
            'job_url' => 'https://example.com/jobs/laravel-developer',
            'job_description' => str_repeat('Build Laravel APIs and backend services. ', 4),
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createAnalysis(User $user, array $overrides = []): Analysis
    {
        $resume = Resume::create($this->resumeAttributes($user));

        return Analysis::create(array_merge([
            'user_id' => $user->id,
            'resume_id' => $resume->id,
            'job_title' => 'Laravel Developer',
            'company_name' => 'ApplyAI',
            'job_url' => 'https://example.com/jobs/laravel-developer',
            'job_description' => str_repeat('Build Laravel APIs and backend services. ', 4),
            'status' => AnalysisStatus::Pending,
        ], $overrides));
    }
}
