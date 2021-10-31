<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Typecast\Fixture;

class Wrapper
{
    public function __construct(
        public mixed $value
    ) {
    }

    public function __toString(): string
    {
        return (string)$this->value;
    }
}
