<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Regression\Issue422\Entity;

class User
{
    public ?int $id = null;
    public ?Billing $billing = null;
    public SomeEmbedded $otherEmbedded;
}
