<?php

namespace App\Domains\Resume\Dto;

use App\Http\Requests\Resume\UploadResumeRequest;
use Illuminate\Http\UploadedFile;

final readonly class UploadResumeDto
{
    public function __construct(
        public int $userId,
        public UploadedFile $resume,
    ) {}

    public static function fromRequest(UploadResumeRequest $request): self
    {
        return new self(
            userId: $request->user()->id,
            resume: $request->file('resume'),
        );
    }
}