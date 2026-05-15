<?php

declare(strict_types=1);

namespace App\Domains\Resume\Services;

use Barryvdh\DomPDF\PDF;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

final class ResumePdfGeneratorService
{
    /**
     * @var array<string, string>
     */
    private const TEMPLATES = [
        'harvard' => 'resumes.pdf.harvard',
    ];

    public function __construct(
        private readonly Container $container,
    ) {}

    /**
     * @param  array<string, mixed>  $structuredResume
     */
    public function generate(array $structuredResume, string $template): string
    {
        $view = $this->resolveView($template);

        return $this->pdf()
            ->loadView($view, [
                'resume' => $structuredResume,
            ])
            ->setPaper('a4', 'portrait')
            ->output();
    }

    private function resolveView(string $template): string
    {
        $template = strtolower(trim($template));

        if (! array_key_exists($template, self::TEMPLATES)) {
            throw new InvalidArgumentException("Resume PDF template [{$template}] is not supported.");
        }

        return self::TEMPLATES[$template];
    }

    private function pdf(): PDF
    {
        return $this->container->make('dompdf.wrapper');
    }
}
