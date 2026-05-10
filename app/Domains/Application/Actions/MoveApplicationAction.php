<?php

declare(strict_types=1);

namespace App\Domains\Application\Actions;

use App\Domains\Application\Dto\MoveApplicationDTO;
use App\Domains\Application\Enums\ApplicationStatus;
use App\Domains\Application\Models\Application;
use Illuminate\Validation\ValidationException;

final class MoveApplicationAction
{
    public function execute(MoveApplicationDTO $dto): Application
    {
        $application = Application::query()->findOrFail($dto->applicationId);

        abort_unless($application->user_id === $dto->userId, 403);

        $application->update([
            'status' => $dto->status->value,
            'position' => $this->position($dto, $application),
        ]);

        return $application->refresh();
    }

    private function position(MoveApplicationDTO $dto, Application $application): float
    {
        $after = $this->neighbor($dto->afterApplicationId, $dto->userId, $dto->status, 'after_application_id');
        $before = $this->neighbor($dto->beforeApplicationId, $dto->userId, $dto->status, 'before_application_id');

        if ($after === null && $before === null && $application->status === $dto->status) {
            return (float) $application->position;
        }

        return match (true) {
            $after && $before => ($after->position + $before->position) / 2,
            $after !== null => $after->position + 1.0,
            $before !== null => $before->position / 2,
            default => $this->nextPosition($dto->userId, $dto->status),
        };
    }

    private function neighbor(?int $id, int $userId, ApplicationStatus $status, string $field): ?Application
    {
        if ($id === null) {
            return null;
        }

        $application = Application::query()->findOrFail($id);

        abort_unless($application->user_id === $userId, 403);

        if ($application->status !== $status) {
            throw ValidationException::withMessages([
                $field => "The selected {$field} must belong to the {$status->value} column.",
            ]);
        }

        return $application;
    }

    private function nextPosition(int $userId, ApplicationStatus $status): float
    {
        $max = Application::query()
            ->where('user_id', $userId)
            ->where('status', $status->value)
            ->max('position');

        return ((float) $max) + 1.0;
    }
}
