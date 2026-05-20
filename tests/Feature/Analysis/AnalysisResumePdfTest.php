<?php

declare(strict_types=1);

namespace Tests\Feature\Analysis;

use App\Domains\Analysis\Enums\AnalysisStatus;
use App\Domains\Analysis\Jobs\PrepareStructuredResumeJob;
use App\Domains\Analysis\Models\Analysis;
use App\Domains\Analysis\Models\AnalysisResult;
use App\Domains\Analysis\Services\ResumeStructuringAgent;
use App\Domains\Analysis\Services\ResumeStructuringService;
use App\Domains\Resume\Models\Resume;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AnalysisResumePdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_generate_harvard_resume_pdf_for_their_analysis(): void
    {
        $user = User::factory()->create();
        $analysis = $this->createAnalysisWithResult($user);

        ResumeStructuringAgent::fake()->preventStrayPrompts();
        cache()->forever(
            app(ResumeStructuringService::class)->cacheKey($analysis),
            $this->structuredResume(),
        );

        $response = $this->withToken($user->createToken('api-token')->plainTextToken)
            ->postJson("/api/analyses/{$analysis->id}/resume/pdf", [
                'template' => 'harvard',
            ]);

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
        $this->assertSame(
            "inline; filename=\"resume-harvard-{$analysis->id}.pdf\"",
            $response->headers->get('content-disposition'),
        );
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_user_can_generate_modern_resume_pdf_for_their_analysis(): void
    {
        $user = User::factory()->create();
        $analysis = $this->createAnalysisWithResult($user);

        ResumeStructuringAgent::fake()->preventStrayPrompts();
        cache()->forever(
            app(ResumeStructuringService::class)->cacheKey($analysis),
            $this->structuredResume(),
        );

        $response = $this->withToken($user->createToken('api-token')->plainTextToken)
            ->postJson("/api/analyses/{$analysis->id}/resume/pdf", [
                'template' => 'modern',
            ]);

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
        $this->assertSame(
            "inline; filename=\"resume-modern-{$analysis->id}.pdf\"",
            $response->headers->get('content-disposition'),
        );
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_user_can_generate_minimal_resume_pdf_for_their_analysis(): void
    {
        $user = User::factory()->create();
        $analysis = $this->createAnalysisWithResult($user);

        ResumeStructuringAgent::fake()->preventStrayPrompts();
        cache()->forever(
            app(ResumeStructuringService::class)->cacheKey($analysis),
            $this->structuredResume(),
        );

        $response = $this->withToken($user->createToken('api-token')->plainTextToken)
            ->postJson("/api/analyses/{$analysis->id}/resume/pdf", [
                'template' => 'minimal',
            ]);

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
        $this->assertSame(
            "inline; filename=\"resume-minimal-{$analysis->id}.pdf\"",
            $response->headers->get('content-disposition'),
        );
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_resume_pdf_generation_returns_conflict_when_structured_resume_is_not_ready(): void
    {
        $user = User::factory()->create();
        $analysis = $this->createAnalysisWithResult($user);

        ResumeStructuringAgent::fake()->preventStrayPrompts();

        $this->withToken($user->createToken('api-token')->plainTextToken)
            ->postJson("/api/analyses/{$analysis->id}/resume/pdf", [
                'template' => 'harvard',
            ])
            ->assertConflict()
            ->assertJson([
                'message' => 'Structured resume is not ready. Start resume structuring and try again once it is ready.',
            ]);
    }

    public function test_user_can_queue_structured_resume_preparation(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $analysis = $this->createAnalysisWithResult($user);

        $this->withToken($user->createToken('api-token')->plainTextToken)
            ->postJson("/api/analyses/{$analysis->id}/resume/structure")
            ->assertAccepted()
            ->assertJsonPath('data.status', 'queued')
            ->assertJsonPath('data.ready', false);

        Queue::assertPushed(PrepareStructuredResumeJob::class);
    }

    public function test_prepare_structured_resume_job_caches_structured_resume(): void
    {
        $user = User::factory()->create();
        $analysis = $this->createAnalysisWithResult($user);
        $structuringService = app(ResumeStructuringService::class);

        ResumeStructuringAgent::fake([$this->structuredResumePayload()])->preventStrayPrompts();

        (new PrepareStructuredResumeJob($analysis->id))->handle($structuringService);

        $this->assertTrue(cache()->has($structuringService->cacheKey($analysis)));
        $this->assertSame(
            ['Built production Laravel APIs with tested queue workflows.', 'Improved PostgreSQL reporting queries for hiring analytics.'],
            cache()->get($structuringService->cacheKey($analysis))['experience'][0]['bullets'],
        );
    }

    public function test_structured_resume_status_reports_ready_from_cache(): void
    {
        $user = User::factory()->create();
        $analysis = $this->createAnalysisWithResult($user);

        cache()->forever(
            app(ResumeStructuringService::class)->cacheKey($analysis),
            $this->structuredResume(),
        );

        $this->withToken($user->createToken('api-token')->plainTextToken)
            ->getJson("/api/analyses/{$analysis->id}/resume/structure/status")
            ->assertOk()
            ->assertJsonPath('data.status', 'ready')
            ->assertJsonPath('data.ready', true);
    }

    public function test_user_cannot_generate_resume_pdf_for_another_users_analysis(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $analysis = $this->createAnalysisWithResult($otherUser);

        ResumeStructuringAgent::fake()->preventStrayPrompts();

        $this->withToken($user->createToken('api-token')->plainTextToken)
            ->postJson("/api/analyses/{$analysis->id}/resume/pdf", [
                'template' => 'harvard',
            ])
            ->assertForbidden();
    }

    public function test_resume_pdf_generation_rejects_unknown_templates(): void
    {
        $user = User::factory()->create();
        $analysis = $this->createAnalysisWithResult($user);

        ResumeStructuringAgent::fake()->preventStrayPrompts();

        $this->withToken($user->createToken('api-token')->plainTextToken)
            ->postJson("/api/analyses/{$analysis->id}/resume/pdf", [
                'template' => 'creative',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['template']);
    }

    public function test_resume_pdf_generation_rejects_unknown_templates_without_json_accept_header(): void
    {
        $user = User::factory()->create();
        $analysis = $this->createAnalysisWithResult($user);

        ResumeStructuringAgent::fake()->preventStrayPrompts();

        $this->withToken($user->createToken('api-token')->plainTextToken)
            ->post("/api/analyses/{$analysis->id}/resume/pdf", [
                'template' => 'harverd',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['template']);
    }

    private function createAnalysisWithResult(User $user): Analysis
    {
        $resume = Resume::create([
            'user_id' => $user->id,
            'original_filename' => 'resume.pdf',
            'stored_path' => 'resumes/resume.pdf',
            'file_size' => 100,
            'mime_type' => 'application/pdf',
            'parse_status' => 'success',
            'extracted_text' => <<<'TEXT'
Jane Doe
jane@example.com

Experience
Backend Developer, ApplyAI, Remote, 2022-Present
- Built APIs.
- Improved queries.

Skills
PHP, Laravel, PostgreSQL

Education
Harvard University, A.B. Computer Science
TEXT,
        ]);

        $analysis = Analysis::create([
            'user_id' => $user->id,
            'resume_id' => $resume->id,
            'job_title' => 'Laravel Developer',
            'company_name' => 'ApplyAI',
            'job_url' => 'https://example.com/jobs/laravel-developer',
            'job_description' => str_repeat('Build Laravel APIs and backend services. ', 4),
            'status' => AnalysisStatus::Completed,
        ]);

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
            'rewritten_bullets' => [
                [
                    'original' => 'Built APIs.',
                    'rewritten' => 'Built production Laravel APIs with tested queue workflows.',
                ],
                [
                    'original' => 'Improved queries.',
                    'rewritten' => 'Improved PostgreSQL reporting queries for hiring analytics.',
                ],
            ],
            'cover_letter' => 'Dear Hiring Manager...',
            'model_used' => 'test-model',
        ]);

        return $analysis->fresh();
    }

    /**
     * @return array<string, mixed>
     */
    private function structuredResumePayload(): array
    {
        return [
            'personal_information' => [
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'phone' => null,
                'location' => null,
                'links' => [],
            ],
            'summary' => null,
            'experience' => [
                [
                    'company' => 'ApplyAI',
                    'title' => 'Backend Developer',
                    'location' => 'Remote',
                    'start_date' => '2022',
                    'end_date' => 'Present',
                    'bullet_indexes' => [0, 1],
                ],
            ],
            'skills' => ['PHP', 'Laravel', 'PostgreSQL'],
            'education' => [
                [
                    'institution' => 'Harvard University',
                    'degree' => 'A.B.',
                    'field' => 'Computer Science',
                    'location' => null,
                    'start_date' => null,
                    'end_date' => null,
                    'details' => [],
                ],
            ],
            'languages' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function structuredResume(): array
    {
        return [
            'personal_information' => [
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'phone' => null,
                'location' => null,
                'links' => [],
            ],
            'summary' => null,
            'experience' => [
                [
                    'company' => 'ApplyAI',
                    'title' => 'Backend Developer',
                    'location' => 'Remote',
                    'start_date' => '2022',
                    'end_date' => 'Present',
                    'bullets' => [
                        'Built production Laravel APIs with tested queue workflows.',
                        'Improved PostgreSQL reporting queries for hiring analytics.',
                    ],
                ],
            ],
            'skills' => ['PHP', 'Laravel', 'PostgreSQL'],
            'education' => [
                [
                    'institution' => 'Harvard University',
                    'degree' => 'A.B.',
                    'field' => 'Computer Science',
                    'location' => null,
                    'start_date' => null,
                    'end_date' => null,
                    'details' => [],
                ],
            ],
            'languages' => [],
        ];
    }
}
