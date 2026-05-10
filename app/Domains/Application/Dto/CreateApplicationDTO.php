<?php

declare(strict_types=1);

namespace App\Domains\Application\Dto;

use App\Http\Requests\Application\CreateApplicationRequest;

final readonly class CreateApplicationDTO
{
    public function __construct(
        public int $userId,
        public ?int $analysisId,
        public string $companyName,
        public string $jobTitle,
        public ?string $jobUrl,
        public ?string $appliedDate,
        public ?string $contactName,
        public ?string $contactEmail,
        public ?string $notes,
    ) {}

    public static function fromRequest(CreateApplicationRequest $request): self
    {
        return new self(
            userId: $request->user()->id,
            analysisId: $request->integer('analysis_id') ?: null,
            companyName: $request->string('company_name')->toString(),
            jobTitle: $request->string('job_title')->toString(),
            jobUrl: $request->filled('job_url') ? $request->string('job_url')->toString() : null,
            appliedDate: $request->filled('applied_date') ? $request->string('applied_date')->toString() : null,
            contactName: $request->filled('contact_name') ? $request->string('contact_name')->toString() : null,
            contactEmail: $request->filled('contact_email') ? $request->string('contact_email')->toString() : null,
            notes: $request->filled('notes') ? $request->string('notes')->toString() : null,
        );
    }
}
