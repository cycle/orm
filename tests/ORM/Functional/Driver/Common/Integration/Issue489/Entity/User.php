<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue489\Entity;

class User
{
    public const ROLE = 'user';

    public ?int $id = null;
    public ?int $user_id = null;

    public ?self $user = null;
    public iterable $users = [];
}
