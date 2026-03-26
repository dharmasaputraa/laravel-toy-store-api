<?php

namespace App\Support;

class Money
{
    public function __construct(
        public float $amount,
        public string $currency = 'IDR'
    ) {}

    public static function make($amount, $currency = 'IDR'): self
    {
        return new self((float) $amount, $currency);
    }

    public function format(): string
    {
        return money_format($this->amount, $this->currency);
    }

    public function raw(): float
    {
        return $this->amount;
    }

    public function add($amount): self
    {
        return new self($this->amount + $amount, $this->currency);
    }

    public function subtract($amount): self
    {
        return new self($this->amount - $amount, $this->currency);
    }
}
