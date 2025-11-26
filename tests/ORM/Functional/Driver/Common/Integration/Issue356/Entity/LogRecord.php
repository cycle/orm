<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue356\Entity;

class LogRecord
{
    public const ROLE = 'log';

    public ?int $id = null;
    public string $message;
    public string $actor_type;
    public int $actor_id;
    public \DateTimeImmutable $created_at;

    public Actor $actor;

    public function __construct(Actor $actor, string $message)
    {
        $this->message = $message;
        $this->actor = $actor;
        $this->created_at = new \DateTimeImmutable();
    }
}
