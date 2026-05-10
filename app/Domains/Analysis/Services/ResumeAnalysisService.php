<?php

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
