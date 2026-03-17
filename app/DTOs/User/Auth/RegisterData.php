<?php


namespace App\DTOs\User\Auth;

use App\Models\User;
use Illuminate\Http\Request;

class RegisterData
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $phone,
        public string $password,
        public string $locale = 'id',
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            email: strtolower($data['email']),
            phone: $data['phone'] ?? null,
            password: $data['password'],
            locale: $data['locale'] ?? 'id',
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
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'password' => $this->password,
            'locale' => $this->locale,
        ];
    }

    public function getEmailDomain(): string
    {
        return substr(strrchr($this->email, "@"), 1);
    }
}
