<?php

namespace App\Domains\Auth\Dto;

use App\Http\Requests\Auth\RegisterRequest;

final readonly class RegisterUserDto
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
    ) {}

    public static function fromRequest(RegisterRequest $request): self
    {
        return new self(
            name: $request->string('name')->toString(),
            email: $request->string('email')->toString(),
            password: $request->string('password')->toString(),
        );
    }
}