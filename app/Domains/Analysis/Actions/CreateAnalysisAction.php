<?php

namespace App\Domains\Analysis\Actions;

use App\Domains\Analysis\Dto\CreateAnalysisDto;
use App\Domains\Analysis\Enums\AnalysisStatus;
use App\Domains\Analysis\Jobs\AnalyzeResumeJob;
use App\Domains\Analysis\Models\Analysis;
use App\Domains\Resume\Models\Resume;
use Illuminate\Validation\ValidationException;

final class CreateAnalysisAction
{
    public function execute(CreateAnalysisDto $dto): Analysis
    {
        $resume = Resume::query()
            ->where('id', $dto->resumeId)
            ->where('user_id', $dto->userId)
            ->first();

        if (! $resume) {
            throw ValidationException::withMessages([
                'resume_id' => ['The selected resume is invalid.'],
            ]);
        }

        if ($resume->parse_status !== 'success') {
            throw ValidationException::withMessages([
                'resume_id' => ['The selected resume has not been parsed successfully.'],
            ]);
        }

        $analysis = Analysis::create([
            'user_id' => $dto->userId,
            'resume_id' => $dto->resumeId,
            'job_title' => $dto->jobTitle,
            'company_name' => $dto->companyName,
            'job_url' => $dto->jobUrl,
            'job_description' => $dto->jobDescription,
            'status' => AnalysisStatus::Pending,
        ]);

        AnalyzeResumeJob::dispatch($analysis->id);

        return $analysis->refresh();
    }
}
