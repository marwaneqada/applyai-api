<?php

namespace App\Domains\Resume\Actions;

use App\Domains\Resume\Models\Resume;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class DeleteResumeAction
{
    public function execute(Resume $resume): void
    {
        try {
            Storage::disk('local')->delete($resume->stored_path);
        } catch (Throwable $e) {
            report($e);
        }

        $resume->delete();
    }
}
