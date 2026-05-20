<?php

declare(strict_types=1);

namespace App\Domains\Resume\Enums;

enum ResumePdfTemplate: string
{
    case Harvard = 'harvard';
    case Modern = 'modern';
    case Minimal = 'minimal';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function view(): string
    {
        return match ($this) {
            self::Harvard => 'resumes.pdf.harvard',
            self::Modern => 'resumes.pdf.modern',
            self::Minimal => 'resumes.pdf.minimal',
        };
    }
}
