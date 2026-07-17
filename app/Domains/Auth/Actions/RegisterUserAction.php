<?php

declare(strict_types=1);

namespace App\Domains\Auth\Actions;

use App\Domains\Auth\Dto\RegisterUserDto;
use App\Domains\Auth\Enums\AccountType;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class RegisterUserAction
{
    public function execute(RegisterUserDto $dto): User
    {
        return DB::transaction(function () use ($dto): User {
            $user = User::create([
                'name' => $dto->name,
                'email' => $dto->email,
                'password' => $dto->password,
                'account_type' => AccountType::Candidate,
            ]);

            $user->candidateProfile()->create();

            return $user;
        });
    }
}
