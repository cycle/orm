<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue380\Entity\User;

use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue380\Entity\User;

class Phone
{
    public int $id;

    public string $value;

    public User $user;

    public function __construct(User $user, string $value)
    {
        $this->user = $user;
        $this->value = $value;
    }
}
