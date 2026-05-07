<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AnalysisResultResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'overall_score' => $this->overall_score,
            'keyword_score' => $this->keyword_score,
            'experience_score' => $this->experience_score,
            'skills_score' => $this->skills_score,
            'matched_keywords' => $this->matched_keywords,
            'missing_keywords' => $this->missing_keywords,
            'strengths' => $this->strengths,
            'weaknesses' => $this->weaknesses,
            'gap_analysis' => $this->gap_analysis,
            'rewritten_bullets' => $this->rewritten_bullets,
            'cover_letter' => $this->cover_letter,
            'model_used' => $this->model_used,
        ];
    }
}
