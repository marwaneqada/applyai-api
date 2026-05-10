<?php

declare(strict_types=1);

namespace App\Domains\Application\Dto;

use App\Domains\Application\Models\Application;
use App\Http\Requests\Application\UpdateApplicationRequest;

final readonly class UpdateApplicationDTO
{
    public function __construct(
        public int $userId,
        public int $applicationId,
        public ?int $analysisId,
        public ?string $companyName,
        public ?string $jobTitle,
        public ?string $jobUrl,
        public ?string $appliedDate,
        public ?string $contactName,
        public ?string $contactEmail,
        public ?string $notes,
        public array $fields,
    ) {}

    public static function fromRequest(UpdateApplicationRequest $request): self
    {
        $application = $request->route('application');

        return new self(
            userId: $request->user()->id,
            applicationId: $application instanceof Application ? $application->id : (int) $application,
            analysisId: $request->integer('analysis_id') ?: null,
            companyName: $request->filled('company_name') ? $request->string('company_name')->toString() : null,
            jobTitle: $request->filled('job_title') ? $request->string('job_title')->toString() : null,
            jobUrl: $request->filled('job_url') ? $request->string('job_url')->toString() : null,
            appliedDate: $request->filled('applied_date') ? $request->string('applied_date')->toString() : null,
            contactName: $request->filled('contact_name') ? $request->string('contact_name')->toString() : null,
            contactEmail: $request->filled('contact_email') ? $request->string('contact_email')->toString() : null,
            notes: $request->filled('notes') ? $request->string('notes')->toString() : null,
            fields: array_keys($request->validated()),
        );
    }
}
