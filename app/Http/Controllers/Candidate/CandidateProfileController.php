<?php

declare(strict_types=1);

namespace App\Http\Controllers\Candidate;

use App\Domains\Candidate\Actions\UpdateCandidateProfileAction;
use App\Domains\Candidate\Dto\UpdateCandidateProfileDto;
use App\Http\Requests\Candidate\UpdateCandidateProfileRequest;
use App\Http\Resources\CandidateProfileResource;
use Illuminate\Http\Request;

final class CandidateProfileController
{
    public function show(Request $request): CandidateProfileResource
    {
        $profile = $request->user()
            ->candidateProfile()
            ->with('user')
            ->firstOrFail();

        return new CandidateProfileResource($profile);
    }

    public function update(
        UpdateCandidateProfileRequest $request,
        UpdateCandidateProfileAction $action,
    ): CandidateProfileResource {
        return new CandidateProfileResource(
            $action->execute(UpdateCandidateProfileDto::fromRequest($request))
        );
    }
}
