<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue356\Entity;

class User implements Actor
{
    public const ROLE = 'user';

    public ?int $id = null;
    public string $name;
    public \DateTimeImmutable $created_at;

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->created_at = new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return "[{$this->id}] {$this->name}";
    }
}
