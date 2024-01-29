<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case318\Entity;

use Ramsey\Uuid\UuidInterface;

class Group
{
    public UuidInterface $uuid;
    public string $title;
}
