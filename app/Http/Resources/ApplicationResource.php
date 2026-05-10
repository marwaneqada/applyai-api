<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domains\Application\Enums\ApplicationStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ApplicationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'analysis_id' => $this->analysis_id,
            'company_name' => $this->company_name,
            'job_title' => $this->job_title,
            'job_url' => $this->job_url,
            'status' => $this->status instanceof ApplicationStatus
                ? $this->status->value
                : $this->status,
            'applied_date' => $this->applied_date?->toDateString(),
            'contact_name' => $this->contact_name,
            'contact_email' => $this->contact_email,
            'notes' => $this->notes,
            'position' => $this->position,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
