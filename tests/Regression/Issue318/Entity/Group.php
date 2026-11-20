<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Regression\Issue318\Entity;

use Ramsey\Uuid\UuidInterface;

class Group
{
    public UuidInterface $uuid;
    public string $title;
}
