<?php

declare(strict_types=1);

namespace Tests\Feature\Resume;

use Tests\TestCase;

class MinimalResumeTemplateTest extends TestCase
{
    public function test_it_renders_structured_resume_data_with_labeled_sections(): void
    {
        $html = view('resumes.pdf.minimal', [
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
                'skills' => ['PHP', 'Laravel', 'PostgreSQL', 'Docker'],
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
        $this->assertStringContainsString('Backend Developer', $html);
        $this->assertStringContainsString('jane@example.com', $html);
        $this->assertStringContainsString('About Me', $html);
        $this->assertStringContainsString('Education', $html);
        $this->assertStringContainsString('Skill', $html);
        $this->assertStringContainsString('Work Experience', $html);
        $this->assertStringContainsString('ApplyAI - Backend Developer', $html);
        $this->assertStringContainsString('2022 - Present', $html);
        $this->assertStringContainsString('PHP', $html);
        $this->assertStringContainsString('Docker', $html);
        $this->assertStringContainsString('Languages', $html);
    }

    public function test_it_compacts_large_resumes_with_late_history_limits(): void
    {
        $html = view('resumes.pdf.minimal', [
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
                            static fn (int $index): string => "Current job bullet {$index} describing important production backend work.",
                            range(1, 10),
                        ),
                    ],
                    [
                        'company' => 'Older Co',
                        'title' => 'Software Engineer',
                        'bullets' => array_map(
                            static fn (int $index): string => "Older job bullet {$index} with useful implementation detail.",
                            range(1, 5),
                        ),
                    ],
                    [
                        'company' => 'Intern Co',
                        'title' => 'Intern',
                        'bullets' => array_map(
                            static fn (int $index): string => "Internship bullet {$index} with early career implementation detail.",
                            range(1, 5),
                        ),
                    ],
                    [
                        'company' => 'Academic Project',
                        'title' => 'Project',
                        'bullets' => array_map(
                            static fn (int $index): string => "Project bullet {$index} with useful academic project detail.",
                            range(1, 5),
                        ),
                    ],
                    [
                        'company' => 'Student Project',
                        'title' => 'Student Developer',
                        'bullets' => array_map(
                            static fn (int $index): string => "Student project bullet {$index} with useful implementation detail.",
                            range(1, 5),
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
        $this->assertStringContainsString('Current job bullet 6', $html);
        $this->assertStringNotContainsString('Current job bullet 7', $html);
        $this->assertStringContainsString('Older job bullet 2', $html);
        $this->assertStringNotContainsString('Older job bullet 3', $html);
        $this->assertStringContainsString('Project bullet 1', $html);
        $this->assertStringNotContainsString('Project bullet 2', $html);
        $this->assertStringContainsString('Skill 16', $html);
        $this->assertStringNotContainsString('Skill 17', $html);
    }
}
