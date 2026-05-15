<?php

declare(strict_types=1);

namespace App\Http\Controllers\Analysis;

use App\Domains\Analysis\Actions\CreateAnalysisAction;
use App\Domains\Analysis\Dto\CreateAnalysisDto;
use App\Domains\Analysis\Jobs\PrepareStructuredResumeJob;
use App\Domains\Analysis\Models\Analysis;
use App\Domains\Analysis\Services\ResumeStructuringService;
use App\Domains\Resume\Services\ResumePdfGeneratorService;
use App\Http\Requests\Analysis\CreateAnalysisRequest;
use App\Http\Requests\Analysis\GenerateResumePdfRequest;
use App\Http\Resources\AnalysisResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

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

    public function status(Request $request, Analysis $analysis): JsonResponse
    {
        abort_unless($analysis->user_id === $request->user()->id, 403);

        return response()->json([
            'data' => [
                'status' => $analysis->status->value,
            ],
        ]);
    }

    public function prepareResumeStructure(
        Request $request,
        Analysis $analysis,
        ResumeStructuringService $structuringService,
    ): JsonResponse {
        abort_unless($analysis->user_id === $request->user()->id, 403);

        if ($structuringService->isCached($analysis)) {
            return response()->json([
                'data' => [
                    'status' => 'ready',
                    'ready' => true,
                ],
            ]);
        }

        PrepareStructuredResumeJob::dispatch($analysis->id);

        return response()->json([
            'data' => [
                'status' => 'queued',
                'ready' => false,
            ],
        ], 202);
    }

    public function resumeStructureStatus(
        Request $request,
        Analysis $analysis,
        ResumeStructuringService $structuringService,
    ): JsonResponse {
        abort_unless($analysis->user_id === $request->user()->id, 403);

        $ready = $structuringService->isCached($analysis);

        return response()->json([
            'data' => [
                'status' => $ready ? 'ready' : 'pending',
                'ready' => $ready,
            ],
        ]);
    }

    public function resumePdf(
        GenerateResumePdfRequest $request,
        Analysis $analysis,
        ResumeStructuringService $structuringService,
        ResumePdfGeneratorService $pdfGeneratorService,
    ): JsonResponse|Response {
        abort_unless($analysis->user_id === $request->user()->id, 403);

        $structuredResume = $structuringService->cached($analysis);

        if ($structuredResume === null) {
            return response()->json([
                'message' => 'Structured resume is not ready. Start resume structuring and try again once it is ready.',
            ], 409);
        }

        $pdf = $pdfGeneratorService->generate(
            $structuredResume,
            $request->string('template')->toString(),
        );
        $filename = sprintf('resume-%s-%d.pdf', $request->string('template')->toString(), $analysis->id);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }
}
