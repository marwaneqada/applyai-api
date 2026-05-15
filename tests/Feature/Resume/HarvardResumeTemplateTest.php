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
        $this->assertStringContainsString('PHP, Laravel, PostgreSQL', $html);
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
}
