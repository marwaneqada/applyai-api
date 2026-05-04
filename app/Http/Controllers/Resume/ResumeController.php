<?php

namespace App\Http\Controllers\Resume;

use App\Domains\Resume\Actions\DeleteResumeAction;
use App\Domains\Resume\Actions\UploadResumeAction;
use App\Domains\Resume\Dto\UploadResumeDto;
use App\Domains\Resume\Models\Resume;
use App\Http\Requests\Resume\UploadResumeRequest;
use App\Http\Resources\ResumeResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

final class ResumeController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $resumes = Resume::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return ResumeResource::collection($resumes);
    }

    public function store(
        UploadResumeRequest $request,
        UploadResumeAction $action
    ): JsonResponse {
        $resume = $action->execute(
            UploadResumeDto::fromRequest($request)
        );

        return (new ResumeResource($resume))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Resume $resume): ResumeResource
    {
        abort_unless($resume->user_id === $request->user()->id, 403);

        return new ResumeResource($resume);
    }

    public function destroy(
        Request $request,
        Resume $resume,
        DeleteResumeAction $action
    ): Response {
        abort_unless($resume->user_id === $request->user()->id, 403);

        $action->execute($resume);

        return response()->noContent();
    }
}
