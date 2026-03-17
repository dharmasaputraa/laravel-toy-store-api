<?php

namespace App\DTOs\User\Auth;

use Illuminate\Http\Request;

class ResetPasswordData
{
    public function __construct(
        public string $token,
        public string $email,
        public string $password,
        public string $password_confirmation,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            token: $data['token'],
            email: $data['email'],
            password: $data['password'],
            password_confirmation: $data['password_confirmation'],
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
            'token' => $this->token,
            'email' => $this->email,
            'password' => $this->password,
            'password_confirmation' => $this->password_confirmation,
        ];
    }
}
