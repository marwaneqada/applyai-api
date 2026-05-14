<?php

declare(strict_types=1);

namespace Tests\Unit\Ai;

use App\Domains\Analysis\Enums\AnalysisStatus;
use App\Domains\Analysis\Models\Analysis;
use App\Domains\Analysis\Models\AnalysisResult;
use App\Domains\Analysis\Services\ResumeStructuringAgent;
use App\Domains\Analysis\Services\ResumeStructuringService;
use App\Domains\Resume\Models\Resume;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class ResumeStructuringServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_structures_resume_data_from_resume_text_and_rewritten_bullets(): void
    {
        ResumeStructuringAgent::fake([$this->validStructuredPayload()])->preventStrayPrompts();

        $result = app(ResumeStructuringService::class)->structure(
            $this->createAnalysisWithResult()
        );

        $this->assertSame('Jane Doe', $result['personal_information']['name']);
        $this->assertSame('Full-stack engineer focused on Laravel APIs.', $result['summary']);
        $this->assertSame('ApplyAI', $result['experience'][0]['company']);
        $this->assertSame([
            'Built production Laravel APIs with tested queue workflows.',
            'Improved PostgreSQL reporting queries for hiring analytics.',
        ], $result['experience'][0]['bullets']);
        $this->assertSame(['PHP', 'Laravel', 'PostgreSQL'], $result['skills']);
        $this->assertSame('State University', $result['education'][0]['institution']);
        $this->assertSame(['English', 'French'], $result['languages']);
    }

    public function test_it_builds_a_prompt_with_resume_text_and_rewritten_bullets(): void
    {
        ResumeStructuringAgent::fake([$this->validStructuredPayload()])->preventStrayPrompts();

        app(ResumeStructuringService::class)->structure(
            $this->createAnalysisWithResult()
        );

        ResumeStructuringAgent::assertPrompted(function ($prompt): bool {
            return $prompt->contains('ORIGINAL RESUME TEXT:')
                && $prompt->contains('Jane Doe')
                && $prompt->contains('REWRITTEN BULLETS IN ORIGINAL RESUME ORDER:')
                && $prompt->contains('Built production Laravel APIs with tested queue workflows.')
                && $prompt->contains('Group rewritten bullets under the correct experience jobs');
        });
    }

    public function test_it_builds_experience_bullets_from_ai_indexes(): void
    {
        ResumeStructuringAgent::fake([
            $this->validStructuredPayload([
                'experience' => [
                    [
                        'company' => 'Consulting Co',
                        'title' => 'Database Consultant',
                        'location' => 'Remote',
                        'start_date' => '2020',
                        'end_date' => '2022',
                        'bullet_indexes' => [1],
                    ],
                    [
                        'company' => 'ApplyAI',
                        'title' => 'Backend Developer',
                        'location' => 'Remote',
                        'start_date' => '2022',
                        'end_date' => 'Present',
                        'bullet_indexes' => [0],
                    ],
                ],
            ]),
        ])->preventStrayPrompts();

        $result = app(ResumeStructuringService::class)->structure(
            $this->createAnalysisWithResult()
        );

        $this->assertSame([
            'Improved PostgreSQL reporting queries for hiring analytics.',
        ], $result['experience'][0]['bullets']);
        $this->assertSame([
            'Built production Laravel APIs with tested queue workflows.',
        ], $result['experience'][1]['bullets']);
    }

    public function test_it_trims_normalized_strings_and_removes_empty_string_list_items(): void
    {
        ResumeStructuringAgent::fake([
            $this->validStructuredPayload([
                'personal_information' => [
                    'name' => '  Jane Doe  ',
                    'email' => ' jane@example.com ',
                    'phone' => '   ',
                    'location' => "\nRemote\n",
                    'links' => [' https://example.com ', '  ', "\t"],
                ],
                'summary' => '  Full-stack engineer focused on Laravel APIs.  ',
                'experience' => [
                    [
                        'company' => ' ApplyAI ',
                        'title' => ' Backend Developer ',
                        'location' => ' Remote ',
                        'start_date' => ' 2022 ',
                        'end_date' => ' Present ',
                        'bullet_indexes' => [0, 1],
                    ],
                ],
                'skills' => [' PHP ', '', ' Laravel ', '   ', ' PostgreSQL '],
                'education' => [
                    [
                        'institution' => ' State University ',
                        'degree' => ' BS ',
                        'field' => ' Computer Science ',
                        'location' => ' ',
                        'start_date' => null,
                        'end_date' => ' ',
                        'details' => [' Honors ', '', '  '],
                    ],
                ],
                'languages' => [' English ', '', ' French '],
            ]),
        ])->preventStrayPrompts();

        $result = app(ResumeStructuringService::class)->structure(
            $this->createAnalysisWithResult()
        );

        $this->assertSame('Jane Doe', $result['personal_information']['name']);
        $this->assertSame('jane@example.com', $result['personal_information']['email']);
        $this->assertNull($result['personal_information']['phone']);
        $this->assertSame('Remote', $result['personal_information']['location']);
        $this->assertSame(['https://example.com'], $result['personal_information']['links']);
        $this->assertSame('Full-stack engineer focused on Laravel APIs.', $result['summary']);
        $this->assertSame('ApplyAI', $result['experience'][0]['company']);
        $this->assertSame('Backend Developer', $result['experience'][0]['title']);
        $this->assertSame('2022', $result['experience'][0]['start_date']);
        $this->assertSame(['PHP', 'Laravel', 'PostgreSQL'], $result['skills']);
        $this->assertSame('State University', $result['education'][0]['institution']);
        $this->assertNull($result['education'][0]['location']);
        $this->assertNull($result['education'][0]['end_date']);
        $this->assertSame(['Honors'], $result['education'][0]['details']);
        $this->assertSame(['English', 'French'], $result['languages']);
    }

    public function test_it_rejects_duplicate_rewritten_bullet_indexes(): void
    {
        ResumeStructuringAgent::fake([
            $this->validStructuredPayload([
                'experience' => [
                    [
                        'company' => 'ApplyAI',
                        'title' => 'Backend Developer',
                        'location' => 'Remote',
                        'start_date' => '2022',
                        'end_date' => 'Present',
                        'bullet_indexes' => [0, 0],
                    ],
                ],
            ]),
        ])->preventStrayPrompts();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Structured resume experience contains duplicate rewritten bullet indexes.');

        app(ResumeStructuringService::class)->structure(
            $this->createAnalysisWithResult()
        );
    }

    public function test_it_rejects_missing_rewritten_bullet_indexes(): void
    {
        ResumeStructuringAgent::fake([
            $this->validStructuredPayload([
                'experience' => [
                    [
                        'company' => 'ApplyAI',
                        'title' => 'Backend Developer',
                        'location' => 'Remote',
                        'start_date' => '2022',
                        'end_date' => 'Present',
                        'bullet_indexes' => [0],
                    ],
                ],
            ]),
        ])->preventStrayPrompts();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Structured resume experience is missing rewritten bullet indexes.');

        app(ResumeStructuringService::class)->structure(
            $this->createAnalysisWithResult()
        );
    }

    public function test_it_rejects_out_of_range_rewritten_bullet_indexes(): void
    {
        ResumeStructuringAgent::fake([
            $this->validStructuredPayload([
                'experience' => [
                    [
                        'company' => 'ApplyAI',
                        'title' => 'Backend Developer',
                        'location' => 'Remote',
                        'start_date' => '2022',
                        'end_date' => 'Present',
                        'bullet_indexes' => [0, 2],
                    ],
                ],
            ]),
        ])->preventStrayPrompts();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Structured resume experience contains an out-of-range rewritten bullet index.');

        app(ResumeStructuringService::class)->structure(
            $this->createAnalysisWithResult()
        );
    }

    public function test_it_caches_structured_resume_by_analysis_id(): void
    {
        ResumeStructuringAgent::fake([$this->validStructuredPayload()])->preventStrayPrompts();

        $analysis = $this->createAnalysisWithResult();
        $service = app(ResumeStructuringService::class);

        $first = $service->structure($analysis);
        $second = $service->structure($analysis->fresh());

        $this->assertSame($first, $second);
        $this->assertTrue(cache()->has($service->cacheKey($analysis)));
    }

    public function test_it_rejects_analysis_without_rewritten_bullets(): void
    {
        ResumeStructuringAgent::fake()->preventStrayPrompts();

        $analysis = $this->createAnalysis();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Analysis result with rewritten bullets is required before resume structuring can run.');

        app(ResumeStructuringService::class)->structure($analysis);
    }

    private function createAnalysisWithResult(): Analysis
    {
        $analysis = $this->createAnalysis();

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

    private function createAnalysis(): Analysis
    {
        $user = User::factory()->create();

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

Summary
Full-stack engineer focused on Laravel APIs.

Experience
Backend Developer, ApplyAI, Remote, 2022-Present
- Built APIs.
- Improved queries.

Skills
PHP, Laravel, PostgreSQL

Education
State University, BS Computer Science

Languages
English, French
TEXT,
        ]);

        return Analysis::create([
            'user_id' => $user->id,
            'resume_id' => $resume->id,
            'job_title' => 'Laravel Developer',
            'company_name' => 'ApplyAI',
            'job_url' => 'https://example.com/jobs/laravel-developer',
            'job_description' => str_repeat('Build Laravel APIs and backend services. ', 4),
            'status' => AnalysisStatus::Completed,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validStructuredPayload(array $overrides = []): array
    {
        return array_merge([
            'personal_information' => [
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'phone' => null,
                'location' => null,
                'links' => [],
            ],
            'summary' => 'Full-stack engineer focused on Laravel APIs.',
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
                    'institution' => 'State University',
                    'degree' => 'BS',
                    'field' => 'Computer Science',
                    'location' => null,
                    'start_date' => null,
                    'end_date' => null,
                    'details' => [],
                ],
            ],
            'languages' => ['English', 'French'],
        ], $overrides);
    }
}
