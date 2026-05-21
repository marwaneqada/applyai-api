<?php

declare(strict_types=1);

namespace App\Domains\Analysis\Services;

use App\Domains\Analysis\Models\Analysis;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use InvalidArgumentException;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\StructuredAgentResponse;
use UnexpectedValueException;

final class ResumeStructuringService
{
    public function __construct(
        private readonly ResumeStructuringAgent $agent,
        private readonly StructuredResumeNormalizer $normalizer,
        private readonly CacheRepository $cache,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function structure(Analysis $analysis): array
    {
        $analysis->loadMissing(['resume', 'result']);

        $resumeText = trim((string) $analysis->resume?->extracted_text);
        $rewrittenBullets = $this->rewrittenBullets($analysis);

        if ($resumeText === '') {
            throw new InvalidArgumentException('Resume text is required before resume structuring can run.');
        }

        return $this->cache->rememberForever(
            $this->cacheKey($analysis),
            fn (): array => $this->structureFresh($analysis, $resumeText, $rewrittenBullets),
        );
    }

    public function cacheKey(Analysis $analysis): string
    {
        return "analysis:{$analysis->id}:structured_resume";
    }

    public function isCached(Analysis $analysis): bool
    {
        return $this->cache->has($this->cacheKey($analysis));
    }

    /**
     * @return array<string, mixed>|null
     */
    public function cached(Analysis $analysis): ?array
    {
        $structuredResume = $this->cache->get($this->cacheKey($analysis));

        return is_array($structuredResume) ? $structuredResume : null;
    }

    /**
     * @param  array<int, string>  $rewrittenBullets
     * @return array<string, mixed>
     */
    private function structureFresh(Analysis $analysis, string $resumeText, array $rewrittenBullets): array
    {
        $response = $this->promptAgent($this->prompt($analysis, $resumeText, $rewrittenBullets));

        if (! $response instanceof StructuredAgentResponse) {
            throw new UnexpectedValueException('AI resume structuring did not return a structured response.');
        }

        return $this->normalizer->normalize($response->structured, $rewrittenBullets);
    }

    /**
     * @return array<int, string>
     */
    private function rewrittenBullets(Analysis $analysis): array
    {
        $result = $analysis->result;

        if ($result === null || ! is_array($result->rewritten_bullets) || $result->rewritten_bullets === []) {
            throw new InvalidArgumentException('Analysis result with rewritten bullets is required before resume structuring can run.');
        }

        return array_map(function (mixed $bullet): string {
            if (is_string($bullet) && trim($bullet) !== '') {
                return $bullet;
            }

            if (is_array($bullet) && isset($bullet['rewritten']) && is_string($bullet['rewritten']) && trim($bullet['rewritten']) !== '') {
                return $bullet['rewritten'];
            }

            throw new InvalidArgumentException('Analysis rewritten bullets must contain non-empty rewritten text.');
        }, array_values($result->rewritten_bullets));
    }

    /**
     * @param  array<int, string>  $rewrittenBullets
     */
    private function prompt(Analysis $analysis, string $resumeText, array $rewrittenBullets): string
    {
        return trim(implode(PHP_EOL.PHP_EOL, [
            "ANALYSIS ID:\n{$analysis->id}",
            "ORIGINAL RESUME TEXT:\n{$resumeText}",
            "REWRITTEN BULLETS IN ORIGINAL RESUME ORDER:\n".$this->encoded($rewrittenBullets),
            <<<'PROMPT'
INSTRUCTIONS:
- Build structured resume data for future PDF templates.
- Use only facts present in the original resume text.
- Use null for missing personal information, dates, titles, companies, locations, summary, degree, institution, or field.
- Use empty arrays for missing links, skills, education details, languages, education, or experience.
- Group rewritten bullets under the correct experience jobs by using the original resume text as the map.
- The rewritten bullets are in the same order as the original resume bullets.
- For each experience item, return bullet_indexes containing zero-based indexes into the rewritten bullets array.
- Use every rewritten bullet index exactly once across all experience items.
- Do not return rewritten bullet text inside experience items.
- Return ONLY valid JSON. No markdown. No explanation. No code blocks. Raw JSON only.
PROMPT,
        ]));
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

    /**
     * @param  array<int, string>  $rewrittenBullets
     */
    private function encoded(array $rewrittenBullets): string
    {
        return json_encode($rewrittenBullets, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
