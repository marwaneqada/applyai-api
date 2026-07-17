<?php

declare(strict_types=1);

namespace App\Domains\Auth\Enums;

enum AccountType: string
{
    case Candidate = 'candidate';
    case Hr = 'hr';
}
