<?php

// phpcs:ignoreFile
declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures;

use Cycle\Database\DatabaseInterface;

class UuidPrimaryKey
{
    private $id;

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public function __toString(): string
    {
        return $this->id;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public static function typecast($value, DatabaseInterface $database)
    {
        return new self($value);
    }
}
