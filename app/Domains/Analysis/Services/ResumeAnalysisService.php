<?php

declare(strict_types=1);

namespace App\Domains\Analysis\Services;

use App\Domains\Analysis\Models\Analysis;
use InvalidArgumentException;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\StructuredAgentResponse;
use UnexpectedValueException;

final class ResumeAnalysisService
{
    public function __construct(
        private readonly ResumeAnalysisAgent $agent,
        private readonly AnalysisResultNormalizer $normalizer,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function analyze(Analysis $analysis): array
    {
        $analysis->loadMissing('resume');

        $resumeText = trim((string) $analysis->resume?->extracted_text);
        $jobDescription = trim($analysis->job_description);

        if ($resumeText === '') {
            throw new InvalidArgumentException('Resume text is required before analysis can run.');
        }

        if ($jobDescription === '') {
            throw new InvalidArgumentException('Job description is required before analysis can run.');
        }

        $response = $this->promptAgent($this->prompt($analysis, $resumeText, $jobDescription));

        if (! $response instanceof StructuredAgentResponse) {
            throw new UnexpectedValueException('AI resume analysis did not return a structured response.');
        }

        return $this->normalizer
            ->normalize($response->structured, $this->resolveModelUsed($response))
            ->toArray();
    }

    private function prompt(Analysis $analysis, string $resumeText, string $jobDescription): string
    {
        return trim(implode(PHP_EOL.PHP_EOL, array_filter([
            "JOB TITLE:\n{$analysis->job_title}",
            $analysis->company_name ? "COMPANY:\n{$analysis->company_name}" : null,
            $analysis->job_url ? "JOB URL:\n{$analysis->job_url}" : null,
            "RESUME TEXT:\n{$resumeText}",
            "JOB DESCRIPTION:\n{$jobDescription}",
            <<<'PROMPT'
INSTRUCTIONS:
- Act as a professional ATS system and career coach.
- Do not invent experience. Only reframe what already exists in the resume.
- For rewritten_bullets: return exactly one object per original bullet point from the resume. Do not add new bullets. Do not merge bullets. The count must match the original. Each object must have two keys: "original" (the exact original bullet text) and "rewritten" (the improved version).
- PDF extraction can flatten visual bullets into plain sentences, remove bullet symbols, or place bullet text before/after the related job heading. Recover those original experience bullets from context.
- Treat action-oriented responsibility, achievement, project, internship, and academic project lines as original bullets when they appear in or near professional experience/project sections, even if no bullet marker survived extraction.
- Include bullets from every job, internship, freelance/project, and academic project found in the resume text. Do not skip older roles or less job-relevant bullets just because the current job description emphasizes newer experience.
- Do not create rewritten_bullets from contact information, skills lists, languages, education metadata, dates, company names, school names, or section headings.
- For gap_analysis: each item must be an object with exactly three keys: "skill" (short skill name), "severity" (one of: critical, important, nice_to_have), "explanation" (one sentence explaining why it matters).
- All score fields must be integers between 0 and 100.
- Return ONLY valid JSON. No markdown. No explanation. No code blocks. Raw JSON only.
PROMPT,
        ])));
    }

    private function promptAgent(string $prompt): AgentResponse
    {
        return $this->agent->prompt(
            $prompt,
            provider: config('services.ai.resume_analysis.provider', 'gemini'),
            model: config('services.ai.resume_analysis.model', 'gemini-2.5-flash'),
            timeout: (int) config('services.ai.resume_analysis.timeout', 60),
        );
    }

    private function resolveModelUsed(StructuredAgentResponse $response): string
    {
        return $response->meta->model
            ?: config('services.ai.resume_analysis.model', 'gemini-2.5-flash');
    }
}
