<?php

namespace App\Domains\Auth\Actions;

use App\Domains\Auth\Dto\RegisterUserDto;
use App\Models\User;

final class RegisterUserAction
{
    public function execute(RegisterUserDto $dto): User
    {
        return User::create([
            'name' => $dto->name,
            'email' => $dto->email,
            'password' => $dto->password,
        ]);
    }
}