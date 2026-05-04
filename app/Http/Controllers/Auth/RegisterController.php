<?php

namespace App\Http\Controllers\Auth;

use App\Domains\Auth\Actions\RegisterUserAction;
use App\Domains\Auth\Dto\RegisterUserDto;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;

final class RegisterController
{
    public function __invoke(RegisterRequest $request, RegisterUserAction $action): JsonResponse
    {
        $user = $action->execute(
            RegisterUserDto::fromRequest($request)
        );

        return response()->json([
            'user' => new UserResource($user),
            'token' => $user->createToken('api-token')->plainTextToken,
        ], 201);
    }
}