<?php

namespace App\Domains\Analysis\Services;

use App\Domains\Analysis\Models\Analysis;

final class FakeAnalysisResultGenerator
{
    /**
     * Temporary static analysis payload used to exercise the queued workflow
     * until the real AI result generator is wired in.
     *
     * @return array<string, mixed>
     */
    public function generate(Analysis $analysis): array
    {
        return [
            'overall_score' => 78,
            'keyword_score' => 82,
            'experience_score' => 74,
            'skills_score' => 80,
            'matched_keywords' => [
                'Laravel',
                'REST API',
                'PostgreSQL',
                'PHP',
            ],
            'missing_keywords' => [
                'Redis',
                'Docker',
                'CI/CD',
            ],
            'strengths' => [
                'Strong backend structure.',
                'Good API development experience.',
                'Clear Laravel project organization.',
            ],
            'weaknesses' => [
                'Missing Docker experience.',
                'Limited DevOps keywords in resume.',
            ],
            'gap_analysis' => [
                'Add more details about queues, background jobs, and deployments.',
                'Mention CI/CD experience if relevant.',
                'Add Docker if you have used it in projects.',
            ],
            'rewritten_bullets' => [
                'Built Laravel APIs using domain-oriented architecture, DTOs, actions, resources, and feature tests.',
                'Implemented authenticated resume upload and PDF parsing flow using Laravel Sanctum and Smalot PDF Parser.',
                'Configured GitHub Actions CI to run automated Laravel tests on every push.',
            ],
            'cover_letter' => 'Dear Hiring Manager, I am excited to apply for this role. My experience building Laravel APIs, implementing authentication, handling file uploads, and structuring backend features makes me a strong fit for this position.',
            'raw_ai_response' => null,
            'model_used' => 'fake-static-result',
        ];
    }
}
