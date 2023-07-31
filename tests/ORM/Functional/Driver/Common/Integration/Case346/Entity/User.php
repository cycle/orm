<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case346\Entity;

use DateTimeImmutable;

class User
{
    public const ROLE = 'user';

    public ?int $id = null;
    public string $login;
    public DateTimeImmutable $created_at;
    public DateTimeImmutable $updated_at;
    /** @var iterable<Post> */
    public iterable $posts = [];

    public function __construct(string $login, string $password)
    {
        $this->login = $login;
        $this->created_at = new DateTimeImmutable();
        $this->updated_at = new DateTimeImmutable();
    }
}
