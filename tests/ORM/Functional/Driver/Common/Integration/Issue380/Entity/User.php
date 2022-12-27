<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case4\Entity;

class User
{
    public int $id;

    public string $username;

    public int $age = 0;

    public function __construct(string $username)
    {
        $this->username = $username;
    }
}
