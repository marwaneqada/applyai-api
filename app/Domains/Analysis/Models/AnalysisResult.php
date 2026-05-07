<?php

namespace App\Domains\Analysis\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'analysis_id',
    'overall_score',
    'keyword_score',
    'experience_score',
    'skills_score',
    'matched_keywords',
    'missing_keywords',
    'strengths',
    'weaknesses',
    'gap_analysis',
    'rewritten_bullets',
    'cover_letter',
    'raw_ai_response',
    'model_used',
])]
class AnalysisResult extends Model
{
    public function analysis(): BelongsTo
    {
        return $this->belongsTo(Analysis::class);
    }

    protected function casts(): array
    {
        return [
            'matched_keywords' => 'array',
            'missing_keywords' => 'array',
            'strengths' => 'array',
            'weaknesses' => 'array',
            'gap_analysis' => 'array',
            'rewritten_bullets' => 'array',
        ];
    }
}