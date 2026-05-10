<?php

declare(strict_types=1);

namespace App\Domains\Application\Actions;

use App\Domains\Application\Models\Application;

final class DeleteApplicationAction
{
    public function execute(Application $application): void
    {
        $application->delete();
    }
}
