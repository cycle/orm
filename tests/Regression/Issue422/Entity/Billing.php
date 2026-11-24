<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Regression\Issue422\Entity;

class Billing
{
    public ?int $id = null;
    public SomeEmbedded $someEmbedded;
}
