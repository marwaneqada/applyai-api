<?php

declare(strict_types=1);

namespace Tests\Feature\Resume;

use Tests\TestCase;

class HarvardResumeTemplateTest extends TestCase
{
    public function test_it_renders_structured_resume_data(): void
    {
        $html = view('resumes.pdf.harvard', [
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
                        'location' => 'Cambridge, MA',
                        'start_date' => '2018',
                        'end_date' => '2022',
                        'details' => ['Dean\'s List'],
                    ],
                ],
                'languages' => ['English', 'French'],
            ],
        ])->render();

        $this->assertStringContainsString('Jane Doe', $html);
        $this->assertStringContainsString('jane@example.com | 555-0100 | Cambridge, MA | https://example.com', $html);
        $this->assertStringContainsString('Summary', $html);
        $this->assertStringContainsString('Experience', $html);
        $this->assertStringContainsString('Built production Laravel APIs with tested queue workflows.', $html);
        $this->assertStringContainsString('Skills', $html);
        $this->assertStringContainsString('Programming:', $html);
        $this->assertStringContainsString('Frameworks:', $html);
        $this->assertStringContainsString('Data:', $html);
        $this->assertStringContainsString('PHP', $html);
        $this->assertStringContainsString('Laravel', $html);
        $this->assertStringContainsString('PostgreSQL', $html);
        $this->assertStringContainsString('Education', $html);
        $this->assertStringContainsString('Harvard University', $html);
        $this->assertStringContainsString('Languages', $html);
        $this->assertStringContainsString('English, French', $html);
    }

    public function test_it_handles_missing_and_null_fields_cleanly(): void
    {
        $html = view('resumes.pdf.harvard', [
            'resume' => [
                'personal_information' => [
                    'name' => null,
                    'email' => 'jane@example.com',
                ],
                'experience' => [
                    [
                        'company' => 'ApplyAI',
                        'title' => null,
                        'bullets' => ['Built APIs.'],
                    ],
                ],
                'education' => [
                    [
                        'institution' => null,
                        'details' => [],
                    ],
                ],
            ],
        ])->render();

        $this->assertStringContainsString('jane@example.com', $html);
        $this->assertStringContainsString('ApplyAI', $html);
        $this->assertStringContainsString('Built APIs.', $html);
        $this->assertStringNotContainsString('Summary</h2>', $html);
        $this->assertStringNotContainsString('Skills</h2>', $html);
        $this->assertStringNotContainsString('Languages</h2>', $html);
    }

    public function test_it_compacts_large_resumes_with_late_history_bullet_limits(): void
    {
        $html = view('resumes.pdf.harvard', [
            'resume' => [
                'personal_information' => [
                    'name' => 'Jane Doe',
                    'email' => 'jane@example.com',
                    'phone' => '555-0100',
                    'location' => 'Cambridge, MA',
                ],
                'summary' => 'Backend engineer focused on reliable systems.',
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
                        'company' => 'Academic Project',
                        'title' => 'Project',
                        'bullets' => array_map(
                            static fn (int $index): string => "Project bullet {$index} with useful academic project detail.",
                            range(1, 4),
                        ),
                    ],
                    [
                        'company' => 'Student Project',
                        'title' => 'Student Developer',
                        'bullets' => array_map(
                            static fn (int $index): string => "Student project bullet {$index} with useful implementation detail.",
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
        $this->assertStringContainsString('Current job bullet 12', $html);
        $this->assertStringContainsString('Older job bullet 4', $html);
        $this->assertStringContainsString('Internship bullet 4', $html);
        $this->assertStringContainsString('Project bullet 4', $html);
        $this->assertStringContainsString('Student project bullet 1', $html);
        $this->assertStringContainsString('Student project bullet 2', $html);
        $this->assertStringContainsString('Student project bullet 3', $html);
        $this->assertStringNotContainsString('Student project bullet 4', $html);
        $this->assertStringContainsString('Skill 24', $html);
        $this->assertStringNotContainsString('Skill 25', $html);
        $this->assertStringContainsString('Long detail B', $html);
        $this->assertStringContainsString('Long detail C', $html);
    }
}
