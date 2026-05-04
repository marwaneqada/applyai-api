<?php

namespace App\Domains\Resume\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'user_id',
    'original_filename',
    'stored_path',
    'file_size',
    'mime_type',
    'parse_status',
    'parse_error',
    'extracted_text',
])]
class Resume extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}