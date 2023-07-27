<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case316;

use Cycle\Database\DatabaseInterface;
use Cycle\Database\Injection\ValueInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class BinaryId implements ValueInterface
{
    public function __construct(
        private UuidInterface $id,
    ) {
    }

    public function __toString()
    {
        return $this->id->toString();
    }

    public function __debugInfo()
    {
        return [
            $this->id->toString(),
        ];
    }

    public function getResource()
    {
        $resource = \fopen('php://memory', 'w+b');
        \fwrite($resource, $this->id->getBytes());
        // \rewind($resource);
        return $resource;
    }

    public function rawValue(): mixed
    {
        // return $this->getResource();
        // return $this->id->getBytes();
        return $this->id->__toString();
    }

    public function rawType(): int
    {
        // return \PDO::PARAM_LOB;
        return \PDO::PARAM_STR;
    }

    public static function create(): self
    {
        return new static(Uuid::uuid7());
    }

    /**
     * @param string|resource $value
     */
    public static function typecast($value, ?DatabaseInterface $db = null): self
    {
        if (\is_resource($value)) {
            // postgres
            $value = \file_get_contents('value', context: $value);
        }
        // var_dump($value);

        return new static(Uuid::fromBytes($value));
    }
}
