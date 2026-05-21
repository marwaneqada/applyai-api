<?php

declare(strict_types=1);

namespace App\Domains\Analysis\Jobs;

use App\Domains\Analysis\Models\Analysis;
use App\Domains\Analysis\Services\ResumeStructuringService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class PrepareStructuredResumeJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly int $analysisId,
    ) {}

    public function handle(ResumeStructuringService $structuringService): void
    {
        $analysis = Analysis::query()->findOrFail($this->analysisId);

        $structuringService->structure($analysis);
    }
}
