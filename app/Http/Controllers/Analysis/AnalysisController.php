<?php

namespace App\Http\Controllers\Analysis;

use App\Domains\Analysis\Actions\CreateAnalysisAction;
use App\Domains\Analysis\Dto\CreateAnalysisDto;
use App\Domains\Analysis\Models\Analysis;
use App\Http\Requests\Analysis\CreateAnalysisRequest;
use App\Http\Resources\AnalysisResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class AnalysisController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $analyses = Analysis::query()
            ->with('result')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return AnalysisResource::collection($analyses);
    }

    public function store(
        CreateAnalysisRequest $request,
        CreateAnalysisAction $action
    ): JsonResponse {
        $analysis = $action->execute(
            CreateAnalysisDto::fromRequest($request)
        );

        return (new AnalysisResource($analysis->load('result')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Analysis $analysis): AnalysisResource
    {
        abort_unless($analysis->user_id === $request->user()->id, 403);

        return new AnalysisResource($analysis->load('result'));
    }
}
