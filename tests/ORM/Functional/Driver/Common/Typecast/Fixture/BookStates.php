<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Typecast\Fixture;

final class BookStates
{
    public array $states;

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
