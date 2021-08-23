<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures;

use Ramsey\Uuid\Uuid as UuidBody;
use Cycle\Database\DatabaseInterface;
use Cycle\Database\Injection\ValueInterface;

class Uuid implements ValueInterface
{
    /** @var UuidBody */
    private $uuid;

    /**
     * @return string
     */
    public function rawValue(): string
    {
        return $this->uuid->getBytes();
    }

    /**
     * @return int
     */
    public function rawType(): int
    {
        return \PDO::PARAM_LOB;
    }

    /**
     * @return string
     */
    public function toString()
    {
        return $this->uuid->toString();
    }

    /**
     * @return Uuid
     * @throws \Exception
     */
    public static function create(): Uuid
    {
        $uuid = new static();
        $uuid->uuid = UuidBody::uuid4();

        return $uuid;
    }

    /**
     * @param string            $value
     * @param DatabaseInterface $db
     * @return Uuid
     */
    public static function parse($value, DatabaseInterface $db): Uuid
    {
        if (is_resource($value)) {
            // postgres
            $value = fread($value, 16);
        }

        $uuid = new static();
        $uuid->uuid = UuidBody::fromBytes((string) $value);

        return $uuid;
    }
}
