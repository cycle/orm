<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures\Enum;

class CustomStringable
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
