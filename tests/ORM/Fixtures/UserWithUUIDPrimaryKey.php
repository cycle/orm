<?php

// phpcs:ignoreFile
declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures;

class UserWithUUIDPrimaryKey implements ImagedInterface
{
    private $uuid;
    private $email;
    private $balance;
    public array $comments = [];

    public function __construct(UuidPrimaryKey $uuid, string $email, float $balance)
    {
        $this->uuid = $uuid;
        $this->email = $email;
        $this->balance = $balance;
    }

    public function getID(): UuidPrimaryKey
    {
        return $this->uuid;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getBalance(): float
    {
        return $this->balance;
    }
}
