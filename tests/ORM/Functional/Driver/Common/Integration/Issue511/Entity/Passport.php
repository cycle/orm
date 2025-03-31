<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue511\Entity;

class Passport
{
    public ?int $id = null;
    public string $number;
    public User $user;
}
