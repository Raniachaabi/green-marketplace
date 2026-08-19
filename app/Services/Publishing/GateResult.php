<?php

namespace App\Services\Publishing;

use Illuminate\Support\Collection;

final class GateResult
{
    /** @param  Collection<int, Violation>  $violations */
    private function __construct(
        public readonly Collection $violations,
    ) {}

    public static function fromViolations(iterable $violations): self
    {
        return new self(collect($violations)->values());
    }

    public function passes(): bool
    {
        return $this->violations->isEmpty();
    }

    public function fails(): bool
    {
        return ! $this->passes();
    }

    public function messages(): array
    {
        return $this->violations->map(fn (Violation $v) => $v->message)->all();
    }

    public function codes(): array
    {
        return $this->violations->map(fn (Violation $v) => $v->code)->unique()->values()->all();
    }

    public function has(string $code): bool
    {
        return $this->violations->contains(fn (Violation $v) => $v->code === $code);
    }

    public function toArray(): array
    {
        return $this->violations->map(fn (Violation $v) => $v->toArray())->all();
    }
}
