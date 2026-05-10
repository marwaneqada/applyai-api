<?php

namespace Tests\Unit\Analysis;

use App\Domains\Analysis\Dto\AnalysisResultDto;
use App\Domains\Analysis\Services\AnalysisResultNormalizer;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class AnalysisResultNormalizerTest extends TestCase
{
    public function test_it_normalizes_a_valid_ai_analysis_payload(): void
    {
        $payload = $this->validPayload([
            'matched_keywords' => [
                'first' => 'Laravel',
                'second' => 'PostgreSQL',
            ],
        ]);

        $result = (new AnalysisResultNormalizer)->normalize($payload, 'test-model');

        $this->assertInstanceOf(AnalysisResultDto::class, $result);
        $this->assertSame(78, $result->overallScore);
        $this->assertSame(['Laravel', 'PostgreSQL'], $result->matchedKeywords);
        $this->assertSame('Generated cover letter.', $result->coverLetter);
        $this->assertSame($this->encoded($payload), $result->rawAiResponse);
        $this->assertSame('test-model', $result->modelUsed);

        $array = $result->toArray();

        $this->assertSame([
            'overall_score',
            'keyword_score',
            'experience_score',
            'skills_score',
            'matched_keywords',
            'missing_keywords',
            'strengths',
            'weaknesses',
            'gap_analysis',
            'rewritten_bullets',
            'cover_letter',
            'raw_ai_response',
            'model_used',
        ], array_keys($array));

        $this->assertSame(78, $array['overall_score']);
        $this->assertSame(['Laravel', 'PostgreSQL'], $array['matched_keywords']);
        $this->assertSame('Generated cover letter.', $array['cover_letter']);
        $this->assertSame($this->encoded($payload), $array['raw_ai_response']);
        $this->assertSame('test-model', $array['model_used']);
    }

    public function test_it_rejects_missing_required_fields(): void
    {
        $payload = $this->validPayload();
        unset($payload['overall_score']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('AI analysis field [overall_score] is required.');

        (new AnalysisResultNormalizer)->normalize($payload, 'test-model');
    }

    public function test_it_rejects_scores_outside_the_allowed_range(): void
    {
        $payload = $this->validPayload([
            'keyword_score' => 101,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('AI analysis field [keyword_score] must be between 0 and 100.');

        (new AnalysisResultNormalizer)->normalize($payload, 'test-model');
    }

    public function test_it_rejects_non_integer_scores(): void
    {
        $payload = $this->validPayload([
            'experience_score' => '88',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('AI analysis field [experience_score] must be an integer.');

        (new AnalysisResultNormalizer)->normalize($payload, 'test-model');
    }

    public function test_it_rejects_non_array_analysis_fields(): void
    {
        $payload = $this->validPayload([
            'strengths' => 'Strong Laravel experience.',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('AI analysis field [strengths] must be an array.');

        (new AnalysisResultNormalizer)->normalize($payload, 'test-model');
    }

    public function test_it_rejects_non_string_cover_letter(): void
    {
        $payload = $this->validPayload([
            'cover_letter' => ['Generated cover letter.'],
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('AI analysis field [cover_letter] must be a string.');

        (new AnalysisResultNormalizer)->normalize($payload, 'test-model');
    }

    public function test_it_requires_a_model_name_when_one_is_not_passed(): void
    {
        $payload = $this->validPayload();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('AI analysis field [model_used] is required.');

        (new AnalysisResultNormalizer)->normalize($payload);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'overall_score' => 78,
            'keyword_score' => 82,
            'experience_score' => 74,
            'skills_score' => 80,
            'matched_keywords' => ['Laravel'],
            'missing_keywords' => ['Docker'],
            'strengths' => ['Strong API experience.'],
            'weaknesses' => ['Needs more DevOps detail.'],
            'gap_analysis' => ['Add deployment examples.'],
            'rewritten_bullets' => ['Built tested Laravel APIs.'],
            'cover_letter' => 'Generated cover letter.',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function encoded(array $payload): string
    {
        return json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
