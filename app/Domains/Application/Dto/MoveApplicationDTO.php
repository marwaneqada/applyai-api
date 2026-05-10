<?php

declare(strict_types=1);

namespace App\Domains\Application\Dto;

use App\Domains\Application\Enums\ApplicationStatus;
use App\Domains\Application\Models\Application;
use App\Http\Requests\Application\MoveApplicationRequest;

final readonly class MoveApplicationDTO
{
    public function __construct(
        public int $userId,
        public int $applicationId,
        public ApplicationStatus $status,
        public ?int $afterApplicationId,
        public ?int $beforeApplicationId,
    ) {}

    public static function fromRequest(MoveApplicationRequest $request): self
    {
        $application = $request->route('application');

        return new self(
            userId: $request->user()->id,
            applicationId: $application instanceof Application ? $application->id : (int) $application,
            status: ApplicationStatus::from($request->string('status')->toString()),
            afterApplicationId: $request->integer('after_application_id') ?: null,
            beforeApplicationId: $request->integer('before_application_id') ?: null,
        );
    }
}
