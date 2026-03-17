<?php

namespace App\DTOs\Health;

class HealthData
{
    public function __construct(
        public bool $success,
        public string $message,
        public array $data,
        public int $statusCode = 200
    ) {}

    public static function make(
        bool $success,
        string $message,
        array $data,
        int $statusCode = 200
    ): self {
        return new self($success, $message, $data, $statusCode);
    }
}
