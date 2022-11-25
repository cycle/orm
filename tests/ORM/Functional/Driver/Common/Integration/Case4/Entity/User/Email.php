<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case4\Entity\User;

use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case4\Entity\User;

class Email
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
