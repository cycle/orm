<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Regression\Issue427\Entity;

use Ramsey\Uuid\UuidInterface;

class User
{
    public ?UuidInterface $id;
    public string $name;
}
