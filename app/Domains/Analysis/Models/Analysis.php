<?php

namespace App\Domains\Analysis\Models;

use App\Domains\Analysis\Enums\AnalysisStatus;
use App\Domains\Resume\Models\Resume;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'user_id',
    'resume_id',
    'job_title',
    'company_name',
    'job_url',
    'job_description',
    'status',
    'error_message',
])]
class Analysis extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resume(): BelongsTo
    {
        return $this->belongsTo(Resume::class);
    }

    public function result(): HasOne
    {
        return $this->hasOne(AnalysisResult::class);
    }

    protected function casts(): array
    {
        return [
            'status' => AnalysisStatus::class,
        ];
    }
}