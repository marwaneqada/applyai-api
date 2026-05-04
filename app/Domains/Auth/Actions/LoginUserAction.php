<?php

namespace App\Domains\Auth\Actions;

use App\Domains\Auth\Dto\LoginUserDto;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class LoginUserAction
{
    /**
     * @throws ValidationException
     */
    public function execute(LoginUserDto $dto): User
    {
        $user = User::query()
            ->where('email', $dto->email)
            ->first();

        if (! $user || ! Hash::check($dto->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        return $user;
    }
}