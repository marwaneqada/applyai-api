<?php

namespace App\Domains\Analysis\Jobs;

use App\Domains\Analysis\Enums\AnalysisStatus;
use App\Domains\Analysis\Models\Analysis;
use App\Domains\Analysis\Services\ResumeAnalysisService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

final class AnalyzeResumeJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly int $analysisId,
    ) {}

    public function handle(ResumeAnalysisService $analysisService): void
    {
        $analysis = Analysis::query()->findOrFail($this->analysisId);

        try {
            $analysis->update([
                'status' => AnalysisStatus::Processing,
                'error_message' => null,
            ]);

            $analysis->result()->updateOrCreate(
                [],
                $analysisService->analyze($analysis),
            );

            $analysis->update([
                'status' => AnalysisStatus::Completed,
                'error_message' => null,
            ]);
        } catch (Throwable $e) {
            report($e);

            $analysis->update([
                'status' => AnalysisStatus::Failed,
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}
