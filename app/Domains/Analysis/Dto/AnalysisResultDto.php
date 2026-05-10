<?php

namespace App\Domains\Analysis\Dto;

final readonly class AnalysisResultDto
{
    /**
     * @param  array<mixed>  $matchedKeywords
     * @param  array<mixed>  $missingKeywords
     * @param  array<mixed>  $strengths
     * @param  array<mixed>  $weaknesses
     * @param  array<mixed>  $gapAnalysis
     * @param  array<mixed>  $rewrittenBullets
     */
    public function __construct(
        public int $overallScore,
        public int $keywordScore,
        public int $experienceScore,
        public int $skillsScore,
        public array $matchedKeywords,
        public array $missingKeywords,
        public array $strengths,
        public array $weaknesses,
        public array $gapAnalysis,
        public array $rewrittenBullets,
        public string $coverLetter,
        public string $rawAiResponse,
        public string $modelUsed,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'overall_score' => $this->overallScore,
            'keyword_score' => $this->keywordScore,
            'experience_score' => $this->experienceScore,
            'skills_score' => $this->skillsScore,
            'matched_keywords' => $this->matchedKeywords,
            'missing_keywords' => $this->missingKeywords,
            'strengths' => $this->strengths,
            'weaknesses' => $this->weaknesses,
            'gap_analysis' => $this->gapAnalysis,
            'rewritten_bullets' => $this->rewrittenBullets,
            'cover_letter' => $this->coverLetter,
            'raw_ai_response' => $this->rawAiResponse,
            'model_used' => $this->modelUsed,
        ];
    }
}
