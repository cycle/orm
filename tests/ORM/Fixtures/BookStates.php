<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures;

final class BookStates
{
    public $states;

    public function __construct(array $states = [])
    {
        $this->states = $states;
    }

    public function __toString(): string
    {
        return implode('|', $this->states);
    }

    public static function cast(string $value): self
    {
        return new self(explode('|', $value));
    }
}
