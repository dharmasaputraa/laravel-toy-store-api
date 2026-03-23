<?php

use Illuminate\Http\Request;

readonly class UpdateParentData
{
    public function __construct(
        public ?string $parent_id
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            parent_id: $request->input('parent_id')
        );
    }
}
