<?php

namespace App\Domains\Analysis\Services;

use App\Domains\Analysis\Dto\AnalysisResultDto;
use InvalidArgumentException;
use JsonException;

final class AnalysisResultNormalizer
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function normalize(array $payload, ?string $modelUsed = null): AnalysisResultDto
    {
        $modelUsed = $modelUsed ?? $this->string($payload, 'model_used');

        if ($modelUsed === '') {
            throw new InvalidArgumentException('AI analysis field [model_used] must not be empty.');
        }

        return new AnalysisResultDto(
            overallScore: $this->score($payload, 'overall_score'),
            keywordScore: $this->score($payload, 'keyword_score'),
            experienceScore: $this->score($payload, 'experience_score'),
            skillsScore: $this->score($payload, 'skills_score'),
            matchedKeywords: $this->array($payload, 'matched_keywords'),
            missingKeywords: $this->array($payload, 'missing_keywords'),
            strengths: $this->array($payload, 'strengths'),
            weaknesses: $this->array($payload, 'weaknesses'),
            gapAnalysis: $this->array($payload, 'gap_analysis'),
            rewrittenBullets: $this->array($payload, 'rewritten_bullets'),
            coverLetter: $this->string($payload, 'cover_letter'),
            rawAiResponse: $this->rawAiResponse($payload),
            modelUsed: $modelUsed,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function score(array $payload, string $field): int
    {
        if (! array_key_exists($field, $payload)) {
            throw new InvalidArgumentException("AI analysis field [{$field}] is required.");
        }

        if (! is_int($payload[$field])) {
            throw new InvalidArgumentException("AI analysis field [{$field}] must be an integer.");
        }

        if ($payload[$field] < 0 || $payload[$field] > 100) {
            throw new InvalidArgumentException("AI analysis field [{$field}] must be between 0 and 100.");
        }

        return $payload[$field];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<mixed>
     */
    private function array(array $payload, string $field): array
    {
        if (! array_key_exists($field, $payload)) {
            throw new InvalidArgumentException("AI analysis field [{$field}] is required.");
        }

        if (! is_array($payload[$field])) {
            throw new InvalidArgumentException("AI analysis field [{$field}] must be an array.");
        }

        return array_values($payload[$field]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function string(array $payload, string $field): string
    {
        if (! array_key_exists($field, $payload)) {
            throw new InvalidArgumentException("AI analysis field [{$field}] is required.");
        }

        if (! is_string($payload[$field])) {
            throw new InvalidArgumentException("AI analysis field [{$field}] must be a string.");
        }

        return $payload[$field];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function rawAiResponse(array $payload): string
    {
        try {
            return json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (JsonException $e) {
            throw new InvalidArgumentException('AI analysis raw payload could not be encoded.', previous: $e);
        }
    }
}
