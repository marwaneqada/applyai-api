<?php

namespace App\Domains\Analysis\Dto;

use App\Http\Requests\Analysis\CreateAnalysisRequest;

final readonly class CreateAnalysisDto
{
    public function __construct(
        public int $userId,
        public int $resumeId,
        public string $jobTitle,
        public ?string $companyName,
        public ?string $jobUrl,
        public string $jobDescription,
    ) {}

    public static function fromRequest(CreateAnalysisRequest $request): self
    {
        return new self(
            userId: $request->user()->id,
            resumeId: $request->integer('resume_id'),
            jobTitle: $request->string('job_title')->toString(),
            companyName: $request->filled('company_name')
                ? $request->string('company_name')->toString()
                : null,
            jobUrl: $request->filled('job_url')
                ? $request->string('job_url')->toString()
                : null,
            jobDescription: $request->string('job_description')->toString(),
        );
    }
}