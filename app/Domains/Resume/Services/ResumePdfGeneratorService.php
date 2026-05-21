<?php

declare(strict_types=1);

namespace App\Domains\Resume\Services;

use App\Domains\Resume\Enums\ResumePdfTemplate;
use Barryvdh\DomPDF\PDF;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

final class ResumePdfGeneratorService
{
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
        $templateName = strtolower(trim($template));
        $template = ResumePdfTemplate::tryFrom($templateName);

        if (! $template instanceof ResumePdfTemplate) {
            throw new InvalidArgumentException("Resume PDF template [{$templateName}] is not supported.");
        }

        return $template->view();
    }

    private function pdf(): PDF
    {
        return $this->container->make('dompdf.wrapper');
    }
}
