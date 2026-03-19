<?php

namespace App\DTOs\User\Auth;

use Illuminate\Http\Request;
use Laravel\Socialite\Contracts\User as SocialUser;

class SocialLoginData
{
    public function __construct(
        public readonly string $provider,
        public readonly string $providerId,
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $avatar,
    ) {}

    public static function fromSocialUser(string $provider, SocialUser $user): self
    {
        return new self(
            provider: $provider,
            providerId: $user->getId(),
            name: $user->getName() ?? $user->getNickname() ?? 'User',
            email: $user->getEmail(),
            avatar: $user->getAvatar(),
        );
    }

    public function toArray(): array
    {
        return [
            'provider' => $this->provider,
            'provider_id' => $this->providerId,
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $this->avatar,
        ];
    }
}
