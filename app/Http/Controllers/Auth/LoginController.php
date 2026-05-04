<?php

namespace App\Http\Controllers\Auth;

use App\Domains\Auth\Actions\LoginUserAction;
use App\Domains\Auth\Dto\LoginUserDto;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;

final class LoginController
{
    public function __invoke(LoginRequest $request, LoginUserAction $action): JsonResponse
    {
        $user = $action->execute(
            LoginUserDto::fromRequest($request)
        );

        return response()->json([
            'user' => new UserResource($user),
            'token' => $user->createToken('api-token')->plainTextToken,
        ]);
    }
}