<?php

namespace App\Http\Resources;

use App\Domains\Analysis\Enums\AnalysisStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AnalysisResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'resume_id' => $this->resume_id,
            'job_title' => $this->job_title,
            'company_name' => $this->company_name,
            'job_url' => $this->job_url,
            'job_description' => $this->job_description,
            'status' => $this->status instanceof AnalysisStatus
                ? $this->status->value
                : $this->status,
            'error_message' => $this->error_message,
            'result' => new AnalysisResultResource($this->whenLoaded('result')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
