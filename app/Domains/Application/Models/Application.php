<?php

declare(strict_types=1);

namespace App\Domains\Application\Models;

use App\Domains\Analysis\Models\Analysis;
use App\Domains\Application\Enums\ApplicationStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'analysis_id',
    'company_name',
    'job_title',
    'job_url',
    'status',
    'applied_date',
    'contact_name',
    'contact_email',
    'notes',
    'position',
])]
class Application extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function analysis(): BelongsTo
    {
        return $this->belongsTo(Analysis::class);
    }

    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
            'applied_date' => 'date',
            'position' => 'float',
        ];
    }
}
