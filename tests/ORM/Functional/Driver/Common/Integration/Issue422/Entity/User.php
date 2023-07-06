<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue422\Entity;

class User
{
    public ?int $id = null;
    public Billing $billing;
}
