<?php

declare(strict_types=1);

namespace App\Domains\Candidate\Dto;

use App\Http\Requests\Candidate\UpdateCandidateProfileRequest;

final readonly class UpdateCandidateProfileDto
{
    /**
     * @param  list<string>  $fields
     */
    public function __construct(
        public int $userId,
        public ?string $name,
        public ?string $headline,
        public ?string $phone,
        public ?string $location,
        public ?string $professionalSummary,
        public ?string $linkedinUrl,
        public ?string $githubUrl,
        public ?string $portfolioUrl,
        public array $fields,
    ) {}

    public static function fromRequest(UpdateCandidateProfileRequest $request): self
    {
        return new self(
            userId: $request->user()->id,
            name: $request->filled('name') ? $request->string('name')->toString() : null,
            headline: $request->filled('headline') ? $request->string('headline')->toString() : null,
            phone: $request->filled('phone') ? $request->string('phone')->toString() : null,
            location: $request->filled('location') ? $request->string('location')->toString() : null,
            professionalSummary: $request->filled('professional_summary') ? $request->string('professional_summary')->toString() : null,
            linkedinUrl: $request->filled('linkedin_url') ? $request->string('linkedin_url')->toString() : null,
            githubUrl: $request->filled('github_url') ? $request->string('github_url')->toString() : null,
            portfolioUrl: $request->filled('portfolio_url') ? $request->string('portfolio_url')->toString() : null,
            fields: array_keys($request->validated()),
        );
    }
}
