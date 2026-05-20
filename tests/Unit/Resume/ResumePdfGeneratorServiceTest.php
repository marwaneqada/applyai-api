<?php

declare(strict_types=1);

namespace Tests\Unit\Resume;

use App\Domains\Resume\Services\ResumePdfGeneratorService;
use InvalidArgumentException;
use Tests\TestCase;

class ResumePdfGeneratorServiceTest extends TestCase
{
    public function test_it_generates_a_harvard_resume_pdf(): void
    {
        $pdf = app(ResumePdfGeneratorService::class)->generate($this->structuredResume(), 'harvard');

        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertGreaterThan(1000, strlen($pdf));
    }

    public function test_it_generates_a_modern_resume_pdf(): void
    {
        $pdf = app(ResumePdfGeneratorService::class)->generate($this->structuredResume(), 'modern');

        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertGreaterThan(1000, strlen($pdf));
    }

    public function test_it_generates_a_minimal_resume_pdf(): void
    {
        $pdf = app(ResumePdfGeneratorService::class)->generate($this->structuredResume(), 'minimal');

        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertGreaterThan(1000, strlen($pdf));
    }

    public function test_it_rejects_unknown_templates(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Resume PDF template [creative] is not supported.');

        app(ResumePdfGeneratorService::class)->generate($this->structuredResume(), 'creative');
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
        ];
    }
}
