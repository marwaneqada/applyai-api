<?php

declare(strict_types=1);

namespace App\Domains\Candidate\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'headline',
    'phone',
    'location',
    'professional_summary',
    'linkedin_url',
    'github_url',
    'portfolio_url',
])]
final class CandidateProfile extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
