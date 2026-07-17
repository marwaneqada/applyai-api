<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domains\Auth\Enums\AccountType;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequireAccountType
{
    public function handle(Request $request, Closure $next, string $accountType): Response
    {
        $requiredAccountType = AccountType::tryFrom($accountType);

        abort_if($requiredAccountType === null, 500, 'Invalid account type middleware configuration.');
        abort_unless($request->user()?->account_type === $requiredAccountType, 403);

        return $next($request);
    }
}
