<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Cycle\Tests\Fixtures;

use Ramsey\Uuid\Uuid;
use Spiral\Database\DatabaseInterface;

class UUIDColumn
{
    /** @var Uuid */
    private $uuid;

    public function __toString()
    {
        return $this->uuid->toString();
    }

    public static function create(): UUIDColumn
    {
        $uuid = new static();
        $uuid->uuid = Uuid::uuid4();
        return $uuid;
    }

    public static function unserialize(string $value, DatabaseInterface $db): UUIDColumn
    {
        $uuid = new static();
        $uuid->uuid = Uuid::fromString($value);

        return $uuid;
    }
}