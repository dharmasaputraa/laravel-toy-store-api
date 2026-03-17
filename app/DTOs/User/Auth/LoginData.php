<?php

namespace App\DTOs\User\Auth;

use Illuminate\Http\Request;

class LoginData
{
    public function __construct(
        public string $email,
        public string $password
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            email: strtolower($data['email']),
            password: $data['password'],
        );
    }

    public static function fromRequest(Request $request): self
    {
        $validated = $request->validated();

        return self::fromArray($validated);
    }

    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'password' => $this->password,
        ];
    }
}
