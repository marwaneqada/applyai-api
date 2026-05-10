<?php

declare(strict_types=1);

namespace App\Domains\Application\Actions;

use App\Domains\Analysis\Models\Analysis;
use App\Domains\Application\Dto\CreateApplicationDTO;
use App\Domains\Application\Enums\ApplicationStatus;
use App\Domains\Application\Models\Application;
use Illuminate\Validation\ValidationException;

final class CreateApplicationAction
{
    public function execute(CreateApplicationDTO $dto): Application
    {
        $this->validateAnalysisOwnership($dto->analysisId, $dto->userId);

        return Application::create([
            'user_id' => $dto->userId,
            'analysis_id' => $dto->analysisId,
            'company_name' => $dto->companyName,
            'job_title' => $dto->jobTitle,
            'job_url' => $dto->jobUrl,
            'status' => ApplicationStatus::Saved->value,
            'applied_date' => $dto->appliedDate,
            'contact_name' => $dto->contactName,
            'contact_email' => $dto->contactEmail,
            'notes' => $dto->notes,
            'position' => $this->nextPosition($dto->userId),
        ]);
    }

    private function validateAnalysisOwnership(?int $analysisId, int $userId): void
    {
        if ($analysisId === null) {
            return;
        }

        $belongsToUser = Analysis::query()
            ->where('id', $analysisId)
            ->where('user_id', $userId)
            ->exists();

        if (! $belongsToUser) {
            throw ValidationException::withMessages([
                'analysis_id' => ['The selected analysis is invalid.'],
            ]);
        }
    }

    private function nextPosition(int $userId): float
    {
        $max = Application::query()
            ->where('user_id', $userId)
            ->where('status', ApplicationStatus::Saved->value)
            ->max('position');

        return ((float) $max) + 1.0;
    }
}
