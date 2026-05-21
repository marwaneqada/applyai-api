<?php

declare(strict_types=1);

namespace Tests\Unit\Ai;

use App\Domains\Analysis\Enums\AnalysisStatus;
use App\Domains\Analysis\Models\Analysis;
use App\Domains\Analysis\Services\ResumeAnalysisAgent;
use App\Domains\Analysis\Services\ResumeAnalysisService;
use App\Domains\Resume\Models\Resume;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class ResumeAnalysisServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_a_normalized_array_from_a_valid_ai_response(): void
    {
        config()->set('services.ai.resume_analysis.model', 'test-ai-model');

        $payload = $this->validAiPayload([
            'matched_keywords' => [
                'first' => 'Laravel',
                'second' => 'PostgreSQL',
            ],
        ]);

        ResumeAnalysisAgent::fake([$payload])->preventStrayPrompts();

        $result = app(ResumeAnalysisService::class)->analyze(
            $this->createAnalysis()
        );

        $this->assertSame(78, $result['overall_score']);
        $this->assertSame(['Laravel', 'PostgreSQL'], $result['matched_keywords']);
        $this->assertSame('Generated cover letter.', $result['cover_letter']);
        $this->assertSame($this->encoded($payload), $result['raw_ai_response']);
        $this->assertSame('test-ai-model', $result['model_used']);
    }

    public function test_it_builds_a_prompt_with_labeled_resume_and_job_description_sections(): void
    {
        ResumeAnalysisAgent::fake([$this->validAiPayload()])->preventStrayPrompts();

        app(ResumeAnalysisService::class)->analyze(
            $this->createAnalysis()
        );

        ResumeAnalysisAgent::assertPrompted(function ($prompt): bool {
            return $prompt->contains('RESUME TEXT:')
                && $prompt->contains('Experienced Laravel developer with PostgreSQL experience.')
                && $prompt->contains('JOB DESCRIPTION:')
                && $prompt->contains('Build Laravel APIs and backend services.');
        });
    }

    public function test_it_instructs_ai_to_recover_bullets_from_flattened_pdf_text(): void
    {
        ResumeAnalysisAgent::fake([$this->validAiPayload()])->preventStrayPrompts();

        app(ResumeAnalysisService::class)->analyze(
            $this->createAnalysis(
                'Professional Experience Built e-commerce site with PrestaShop Created AI mini projects 2022 - 2023 Developer Example Co',
            )
        );

        ResumeAnalysisAgent::assertPrompted(function ($prompt): bool {
            return $prompt->contains('PDF extraction can flatten visual bullets into plain sentences')
                && $prompt->contains('Treat action-oriented responsibility, achievement, project, internship, and academic project lines as original bullets')
                && $prompt->contains('Include bullets from every job, internship, freelance/project, and academic project')
                && $prompt->contains('Do not skip older roles or less job-relevant bullets')
                && $prompt->contains('Built e-commerce site with PrestaShop Created AI mini projects');
        });
    }

    public function test_it_rejects_invalid_ai_payloads_through_the_normalizer(): void
    {
        ResumeAnalysisAgent::fake([
            $this->validAiPayload([
                'overall_score' => 101,
            ]),
        ])->preventStrayPrompts();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('AI analysis field [overall_score] must be between 0 and 100.');

        app(ResumeAnalysisService::class)->analyze(
            $this->createAnalysis()
        );
    }

    private function createAnalysis(string $extractedText = 'Experienced Laravel developer with PostgreSQL experience.'): Analysis
    {
        $user = User::factory()->create();
        $resume = Resume::create([
            'user_id' => $user->id,
            'original_filename' => 'resume.pdf',
            'stored_path' => 'resumes/resume.pdf',
            'file_size' => 100,
            'mime_type' => 'application/pdf',
            'parse_status' => 'success',
            'extracted_text' => $extractedText,
        ]);

        return Analysis::create([
            'user_id' => $user->id,
            'resume_id' => $resume->id,
            'job_title' => 'Laravel Developer',
            'company_name' => 'ApplyAI',
            'job_url' => 'https://example.com/jobs/laravel-developer',
            'job_description' => 'Build Laravel APIs and backend services.',
            'status' => AnalysisStatus::Pending,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validAiPayload(array $overrides = []): array
    {
        return array_merge([
            'overall_score' => 78,
            'keyword_score' => 82,
            'experience_score' => 74,
            'skills_score' => 80,
            'matched_keywords' => ['Laravel'],
            'missing_keywords' => ['Docker'],
            'strengths' => ['Strong API experience.'],
            'weaknesses' => ['Needs more DevOps detail.'],
            'gap_analysis' => ['Add deployment examples.'],
            'rewritten_bullets' => ['Built tested Laravel APIs.'],
            'cover_letter' => 'Generated cover letter.',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function encoded(array $payload): string
    {
        return json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
