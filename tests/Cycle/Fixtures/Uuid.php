<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Cycle\Tests\Fixtures;

use Ramsey\Uuid\Uuid as UuidBody;
use Spiral\Database\DatabaseInterface;
use Spiral\Database\Injection\ValueInterface;

class Uuid implements ValueInterface
{
    /** @var UuidBody */
    private $uuid;

    public function toString()
    {
        return $this->uuid->toString();
    }

    /**
     * @return string
     */
    public function rawValue(): string
    {
        return $this->uuid->getBytes();
    }

    public static function create(): Uuid
    {
        $uuid = new static();
        $uuid->uuid = UuidBody::uuid4();

        return $uuid;
    }

    public static function read(string $value, DatabaseInterface $db): Uuid
    {
        $uuid = new static();
        $uuid->uuid = UuidBody::fromBytes($value);

        return $uuid;
    }
}