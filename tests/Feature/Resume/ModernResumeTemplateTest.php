<?php

declare(strict_types=1);

namespace Tests\Feature\Resume;

use Tests\TestCase;

class ModernResumeTemplateTest extends TestCase
{
    public function test_it_renders_structured_resume_data(): void
    {
        $html = view('resumes.pdf.modern', [
            'resume' => [
                'personal_information' => [
                    'name' => 'Jane Doe',
                    'email' => 'jane@example.com',
                    'phone' => '555-0100',
                    'location' => 'Cambridge, MA',
                    'links' => ['https://example.com'],
                ],
                'summary' => 'Backend engineer focused on reliable Laravel APIs.',
                'experience' => [
                    [
                        'company' => 'ApplyAI',
                        'title' => 'Backend Developer',
                        'location' => 'Remote',
                        'start_date' => '2022',
                        'end_date' => 'Present',
                        'bullets' => [
                            'Built production Laravel APIs with tested queue workflows.',
                        ],
                    ],
                ],
                'skills' => ['PHP', 'Laravel', 'PostgreSQL'],
                'education' => [
                    [
                        'institution' => 'Harvard University',
                        'degree' => 'A.B.',
                        'field' => 'Computer Science',
                        'location' => 'Cambridge, MA',
                        'start_date' => '2018',
                        'end_date' => '2022',
                        'details' => [],
                    ],
                ],
                'languages' => ['English', 'French'],
            ],
        ])->render();

        $this->assertStringContainsString('Jane Doe', $html);
        $this->assertStringContainsString('jane@example.com', $html);
        $this->assertStringContainsString('Contact', $html);
        $this->assertStringContainsString('Summary', $html);
        $this->assertStringContainsString('Work Experience', $html);
        $this->assertStringContainsString('Backend Developer', $html);
        $this->assertStringContainsString('Skills', $html);
        $this->assertStringContainsString('PHP', $html);
        $this->assertStringContainsString('Laravel', $html);
        $this->assertStringContainsString('PostgreSQL', $html);
        $this->assertStringContainsString('Education', $html);
        $this->assertStringContainsString('Language', $html);
    }

    public function test_it_uses_relaxed_mode_for_small_resumes_and_keeps_detail(): void
    {
        $html = view('resumes.pdf.modern', [
            'resume' => [
                'personal_information' => [
                    'name' => 'Jane Doe',
                    'links' => ['https://example.com', 'https://github.com/jane', 'https://linkedin.com/in/jane'],
                ],
                'summary' => 'Backend engineer focused on reliable APIs.',
                'experience' => [
                    [
                        'company' => 'ApplyAI',
                        'title' => 'Backend Developer',
                        'bullets' => [
                            'Built production Laravel APIs.',
                            'Improved queue workflows.',
                            'Optimized database queries.',
                            'Supported production deployments.',
                            'Documented backend services.',
                            'Mentored junior developers.',
                            'Improved monitoring dashboards.',
                            'Reduced operational incidents.',
                        ],
                    ],
                ],
                'skills' => ['PHP', 'Laravel', 'PostgreSQL'],
                'education' => [
                    [
                        'institution' => 'Harvard University',
                        'degree' => 'A.B.',
                        'field' => 'Computer Science',
                        'details' => ['Dean\'s List', 'Capstone project', 'Research assistant'],
                    ],
                ],
            ],
        ])->render();

        $this->assertStringContainsString('resume-relaxed', $html);
        $this->assertStringContainsString('Reduced operational incidents.', $html);
        $this->assertStringContainsString('Research assistant', $html);
        $this->assertStringContainsString('https://linkedin.com/in/jane', $html);
    }

    public function test_it_uses_compact_mode_for_large_resumes_without_over_trimming(): void
    {
        $html = view('resumes.pdf.modern', [
            'resume' => [
                'personal_information' => [
                    'name' => 'Jane Doe',
                    'links' => ['https://example.com', 'https://github.com/jane', 'https://linkedin.com/in/jane'],
                ],
                'summary' => 'Experienced backend engineer building reliable systems.',
                'experience' => [
                    [
                        'company' => 'ApplyAI',
                        'title' => 'Backend Developer',
                        'bullets' => array_map(
                            static fn (int $index): string => "Current job bullet {$index} describing important production backend work across APIs, queues, and databases.",
                            range(1, 12),
                        ),
                    ],
                    [
                        'company' => 'Older Co',
                        'title' => 'Software Engineer',
                        'bullets' => array_map(
                            static fn (int $index): string => "Older job bullet {$index} with useful implementation detail.",
                            range(1, 4),
                        ),
                    ],
                    [
                        'company' => 'Intern Co',
                        'title' => 'Intern',
                        'bullets' => array_map(
                            static fn (int $index): string => "Internship bullet {$index} with early career implementation detail.",
                            range(1, 4),
                        ),
                    ],
                    [
                        'company' => 'Support Co',
                        'title' => 'Support Developer',
                        'bullets' => array_map(
                            static fn (int $index): string => "Support job bullet {$index} with useful implementation detail.",
                            range(1, 4),
                        ),
                    ],
                    [
                        'company' => 'Student Co',
                        'title' => 'Student Developer',
                        'bullets' => array_map(
                            static fn (int $index): string => "Student job bullet {$index} with useful implementation detail.",
                            range(1, 4),
                        ),
                    ],
                ],
                'skills' => array_map(static fn (int $index): string => "Skill {$index}", range(1, 24)),
                'education' => [
                    [
                        'institution' => 'University One',
                        'details' => ['Long detail A', 'Long detail B', 'Long detail C'],
                    ],
                    [
                        'institution' => 'University Two',
                        'details' => ['Long detail D', 'Long detail E', 'Long detail F'],
                    ],
                    [
                        'institution' => 'University Three',
                        'details' => ['Long detail G', 'Long detail H', 'Long detail I'],
                    ],
                ],
            ],
        ])->render();

        $this->assertStringContainsString('resume-compact', $html);
        $this->assertStringContainsString('Current job bullet 7', $html);
        $this->assertStringNotContainsString('Current job bullet 8', $html);
        $this->assertStringContainsString('Older job bullet 3', $html);
        $this->assertStringNotContainsString('Older job bullet 4', $html);
        $this->assertStringContainsString('Support job bullet 1', $html);
        $this->assertStringNotContainsString('Support job bullet 2', $html);
        $this->assertStringContainsString('Student job bullet 1', $html);
        $this->assertStringNotContainsString('Student job bullet 2', $html);
        $this->assertStringContainsString('Skill 18', $html);
        $this->assertStringNotContainsString('Skill 19', $html);
        $this->assertStringContainsString('https://github.com/jane', $html);
        $this->assertStringNotContainsString('https://linkedin.com/in/jane', $html);
        $this->assertStringContainsString('Long detail B', $html);
        $this->assertStringNotContainsString('Long detail C', $html);
        $this->assertStringContainsString('Experienced backend engineer building reliable systems.', $html);
    }

    public function test_it_reuses_empty_older_job_bullet_budget_for_jobs_with_bullets(): void
    {
        $html = view('resumes.pdf.modern', [
            'resume' => [
                'personal_information' => [
                    'name' => 'Jane Doe',
                ],
                'summary' => 'Experienced backend engineer building reliable systems.',
                'experience' => [
                    [
                        'company' => 'ApplyAI',
                        'title' => 'Backend Developer',
                        'bullets' => array_map(
                            static fn (int $index): string => "Current job bullet {$index} describing important production backend work.",
                            range(1, 12),
                        ),
                    ],
                    [
                        'company' => 'Older Co',
                        'title' => 'Software Engineer',
                        'bullets' => [],
                    ],
                    [
                        'company' => 'Intern Co',
                        'title' => 'Intern',
                        'bullets' => ['Internship bullet 1 with early career implementation detail.'],
                    ],
                    [
                        'company' => 'Support Co',
                        'title' => 'Support Developer',
                        'bullets' => [],
                    ],
                    [
                        'company' => 'Student Co',
                        'title' => 'Student Developer',
                        'bullets' => ['Student job bullet 1 with useful implementation detail.'],
                    ],
                ],
                'skills' => array_map(static fn (int $index): string => "Skill {$index}", range(1, 18)),
                'education' => [
                    ['institution' => 'University One'],
                    ['institution' => 'University Two'],
                    ['institution' => 'University Three'],
                ],
            ],
        ])->render();

        $this->assertStringContainsString('Current job bullet 10', $html);
        $this->assertStringContainsString('Internship bullet 1', $html);
        $this->assertStringContainsString('Student job bullet 1', $html);
    }

    public function test_it_uses_dense_mode_only_for_huge_resumes(): void
    {
        $longBullet = 'describing broad production backend ownership across APIs, queues, reporting workflows, data reliability, monitoring, and operational delivery.';

        $html = view('resumes.pdf.modern', [
            'resume' => [
                'personal_information' => [
                    'name' => 'Jane Doe',
                    'links' => ['https://example.com', 'https://github.com/jane', 'https://linkedin.com/in/jane'],
                ],
                'summary' => str_repeat('Experienced backend engineer building reliable systems for complex teams. ', 10),
                'experience' => array_map(
                    static fn (int $jobIndex): array => [
                        'company' => "Company {$jobIndex}",
                        'title' => $jobIndex === 1 ? 'Backend Developer' : "Software Engineer {$jobIndex}",
                        'bullets' => array_map(
                            static fn (int $bulletIndex): string => "Job {$jobIndex} bullet {$bulletIndex} {$longBullet}",
                            range(1, 12),
                        ),
                    ],
                    range(1, 5),
                ),
                'skills' => array_map(static fn (int $index): string => "Skill {$index}", range(1, 32)),
                'education' => array_map(
                    static fn (int $index): array => [
                        'institution' => "University {$index}",
                        'details' => ["Detail {$index} A", "Detail {$index} B", "Detail {$index} C"],
                    ],
                    range(1, 4),
                ),
            ],
        ])->render();

        $this->assertStringContainsString('resume-dense', $html);
        $this->assertStringContainsString('Job 1 bullet 5', $html);
        $this->assertStringNotContainsString('Job 1 bullet 6', $html);
        $this->assertStringContainsString('Job 2 bullet 2', $html);
        $this->assertStringNotContainsString('Job 2 bullet 3', $html);
        $this->assertStringContainsString('Skill 14', $html);
        $this->assertStringNotContainsString('Skill 15', $html);
        $this->assertStringContainsString('https://example.com', $html);
        $this->assertStringContainsString('https://github.com/jane', $html);
        $this->assertStringNotContainsString('https://linkedin.com/in/jane', $html);
        $this->assertStringContainsString('Detail 1 A', $html);
        $this->assertStringNotContainsString('Detail 1 B', $html);
    }
}
