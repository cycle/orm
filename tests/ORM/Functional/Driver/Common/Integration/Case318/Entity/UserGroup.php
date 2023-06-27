<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case318\Entity;

use Ramsey\Uuid\UuidInterface;

class UserGroup
{
    public UuidInterface $uuid;

    public UuidInterface $user_id;
    public UuidInterface $group_id;
}
