<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case427\Entity;

use Ramsey\Uuid\UuidInterface;

class User
{
    public ?UuidInterface $id;
    public string $name;
}
