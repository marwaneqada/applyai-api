<?php

declare(strict_types=1);

namespace App\Domains\Candidate\Actions;

use App\Domains\Candidate\Dto\UpdateCandidateProfileDto;
use App\Domains\Candidate\Models\CandidateProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class UpdateCandidateProfileAction
{
    public function execute(UpdateCandidateProfileDto $dto): CandidateProfile
    {
        $user = User::query()->findOrFail($dto->userId);

        return DB::transaction(function () use ($dto, $user): CandidateProfile {
            if (in_array('name', $dto->fields, true)) {
                $user->update(['name' => $dto->name]);
            }

            $profile = $user->candidateProfile()->firstOrFail();
            $profile->update($this->profileAttributes($dto));

            return $profile->refresh()->load('user');
        });
    }

    /**
     * @return array<string, string|null>
     */
    private function profileAttributes(UpdateCandidateProfileDto $dto): array
    {
        $attributes = [
            'headline' => $dto->headline,
            'phone' => $dto->phone,
            'location' => $dto->location,
            'professional_summary' => $dto->professionalSummary,
            'linkedin_url' => $dto->linkedinUrl,
            'github_url' => $dto->githubUrl,
            'portfolio_url' => $dto->portfolioUrl,
        ];

        return array_intersect_key($attributes, array_flip($dto->fields));
    }
}
