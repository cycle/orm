<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue422\Entity;

use DateTimeImmutable;

class Billing
{
    public ?int $id = null;

    public SomeEmbedded $someEmbedded;
}
