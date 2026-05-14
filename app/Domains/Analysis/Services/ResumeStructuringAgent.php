<?php

declare(strict_types=1);

namespace App\Domains\Analysis\Services;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

final class ResumeStructuringAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return <<<'PROMPT'
You structure resume text for a future PDF template.
Return ONLY valid JSON. No markdown. No explanation.
Do not invent contact information, experience, education, skills, or languages.
Return bullet indexes for experience items instead of bullet text.
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
        $nullableString = fn () => $schema->string()->nullable()->required();

        return [
            'personal_information' => $schema->object([
                'name' => $nullableString(),
                'email' => $nullableString(),
                'phone' => $nullableString(),
                'location' => $nullableString(),
                'links' => $schema->array()->items($schema->string())->required(),
            ])->withoutAdditionalProperties()->required(),
            'summary' => $nullableString(),
            'experience' => $schema->array()->items($schema->object([
                'company' => $nullableString(),
                'title' => $nullableString(),
                'location' => $nullableString(),
                'start_date' => $nullableString(),
                'end_date' => $nullableString(),
                'bullet_indexes' => $schema->array()->items($schema->integer()->min(0))->required(),
            ])->withoutAdditionalProperties())->required(),
            'skills' => $schema->array()->items($schema->string())->required(),
            'education' => $schema->array()->items($schema->object([
                'institution' => $nullableString(),
                'degree' => $nullableString(),
                'field' => $nullableString(),
                'location' => $nullableString(),
                'start_date' => $nullableString(),
                'end_date' => $nullableString(),
                'details' => $schema->array()->items($schema->string())->required(),
            ])->withoutAdditionalProperties())->required(),
            'languages' => $schema->array()->items($schema->string())->required(),
        ];
    }
}
