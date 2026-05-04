<?php

namespace App\Domains\Resume\Services;

use Smalot\PdfParser\Parser;
use Throwable;

final class PdfTextExtractor
{
    public function extract(string $filePath): string
    {
        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($filePath);

            return $this->cleanText($pdf->getText());
        } catch (Throwable $e) {
            throw new \RuntimeException('Unable to extract text from the PDF file.');
        }
    }

    private function cleanText(string $text): string
    {
        $text = preg_replace('/\s+/', ' ', $text) ?? '';

        return trim($text);
    }
}