<?php

namespace App\Domains\Analysis\Services;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

final class ResumeAnalysisAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return <<<'PROMPT'
You are a professional ATS and career coach.
Return ONLY valid JSON. No markdown. No explanation.
Do not invent experience. Only reframe what exists in the resume.
PROMPT;
    }

    public function temperature(): float
    {
        return (float) config('services.ai.resume_analysis.temperature', 0.1);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'overall_score' => $schema->integer()->min(0)->max(100)->required(),
            'keyword_score' => $schema->integer()->min(0)->max(100)->required(),
            'experience_score' => $schema->integer()->min(0)->max(100)->required(),
            'skills_score' => $schema->integer()->min(0)->max(100)->required(),
            'matched_keywords' => $schema->array()->items($schema->string())->required(),
            'missing_keywords' => $schema->array()->items($schema->string())->required(),
            'strengths' => $schema->array()->items($schema->string())->required(),
            'weaknesses' => $schema->array()->items($schema->string())->required(),
            'gap_analysis' => $schema->array()->items($schema->object([
                'skill' => $schema->string()->required(),
                'severity' => $schema->string()->enum(['critical', 'important', 'nice_to_have'])->required(),
                'explanation' => $schema->string()->required(),
            ])->withoutAdditionalProperties())->required(),
            'rewritten_bullets' => $schema->array()->items($schema->object([
                'original' => $schema->string()->required(),
                'rewritten' => $schema->string()->required(),
            ])->withoutAdditionalProperties())->required(),
            'cover_letter' => $schema->string()->required(),
        ];
    }
}
