<?php

namespace App\Domains\Auth\Dto;

use App\Http\Requests\Auth\LoginRequest;

final readonly class LoginUserDto
{
    public function __construct(
        public string $email,
        public string $password,
    ) {}

    public static function fromRequest(LoginRequest $request): self
    {
        return new self(
            email: $request->string('email')->toString(),
            password: $request->string('password')->toString(),
        );
    }
}