<?php

namespace App\Domains\Resume\Actions;

use App\Domains\Resume\Dto\UploadResumeDto;
use App\Domains\Resume\Models\Resume;
use App\Domains\Resume\Services\PdfTextExtractor;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class UploadResumeAction
{
    public function __construct(
        private readonly PdfTextExtractor $pdfTextExtractor,
    ) {}

    public function execute(UploadResumeDto $dto): Resume
    {
        $file = $dto->resume;

        $path = $file->store('resumes', 'local');

        try {
            $resume = Resume::create([
                'user_id' => $dto->userId,
                'original_filename' => $file->getClientOriginalName(),
                'stored_path' => $path,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'parse_status' => 'pending',
            ]);
        } catch (Throwable $e) {
            Storage::disk('local')->delete($path);

            throw $e;
        }

        try {
            $text = $this->pdfTextExtractor->extract(
                Storage::disk('local')->path($path)
            );

            if (mb_strlen($text) < 100) {
                throw new \RuntimeException('The PDF does not contain enough readable text.');
            }

            $resume->update([
                'parse_status' => 'success',
                'extracted_text' => $text,
            ]);
        } catch (Throwable $e) {
            $resume->update([
                'parse_status' => 'failed',
                'parse_error' => $e->getMessage(),
            ]);
        }

        return $resume->refresh();
    }
}
