<?php

declare(strict_types=1);

namespace App\Domains\Application\Actions;

use App\Domains\Analysis\Models\Analysis;
use App\Domains\Application\Dto\UpdateApplicationDTO;
use App\Domains\Application\Models\Application;
use Illuminate\Validation\ValidationException;

final class UpdateApplicationAction
{
    public function execute(UpdateApplicationDTO $dto): Application
    {
        $application = Application::query()->findOrFail($dto->applicationId);

        abort_unless($application->user_id === $dto->userId, 403);

        $this->validateAnalysisOwnership($dto);

        $application->update($this->attributes($dto));

        return $application->refresh();
    }

    private function validateAnalysisOwnership(UpdateApplicationDTO $dto): void
    {
        if (! in_array('analysis_id', $dto->fields, true) || $dto->analysisId === null) {
            return;
        }

        $belongsToUser = Analysis::query()
            ->where('id', $dto->analysisId)
            ->where('user_id', $dto->userId)
            ->exists();

        if (! $belongsToUser) {
            throw ValidationException::withMessages([
                'analysis_id' => ['The selected analysis is invalid.'],
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function attributes(UpdateApplicationDTO $dto): array
    {
        $map = [
            'analysis_id' => $dto->analysisId,
            'company_name' => $dto->companyName,
            'job_title' => $dto->jobTitle,
            'job_url' => $dto->jobUrl,
            'applied_date' => $dto->appliedDate,
            'contact_name' => $dto->contactName,
            'contact_email' => $dto->contactEmail,
            'notes' => $dto->notes,
        ];

        return array_intersect_key($map, array_flip($dto->fields));
    }
}
