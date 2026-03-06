<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case431\Typecast;

use Cycle\Database\Injection\ValueInterface;

/**
 * UUID that implements both ValueInterface and Stringable with DIFFERENT representations.
 * rawValue() returns the UUID string matching the DB value.
 * __toString() returns a different format (urn:uuid:...) to prove rawValue() takes priority.
 */
class ValueInterfaceUuid implements ValueInterface, \Stringable
{
    public function __construct(
        private readonly string $value,
    ) {}

    public function rawValue(): string
    {
        return $this->value;
    }

    public function rawType(): int
    {
        return \PDO::PARAM_STR;
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return 'urn:uuid:' . $this->value;
    }
}
