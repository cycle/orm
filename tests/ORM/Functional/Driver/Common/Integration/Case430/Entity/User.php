<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case430\Entity;

use Ramsey\Uuid\UuidInterface;

class User
{
    public const ROLE = 'user';

    public UuidInterface $uuid;
    public string $login;
    public string $passwordHash;
    public \DateTimeImmutable $created_at;
    public \DateTimeImmutable $updated_at;

    /** @var iterable<Post> */
    public iterable $posts = [];

    /** @var iterable<Comment> */
    public iterable $comments = [];
}
