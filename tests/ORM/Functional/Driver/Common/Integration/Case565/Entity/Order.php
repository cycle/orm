<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case565\Entity;

class Order
{
    public ?int $tenant_id = null;
    public ?int $number = null;
    public float $total = 0.0;
    public ?OrderShipping $shipping = null;
}
