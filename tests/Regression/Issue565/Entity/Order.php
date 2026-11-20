<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Regression\Issue565\Entity;

class Order
{
    public ?int $tenant_id = null;
    public ?int $number = null;
    public float $total = 0.0;
    public ?OrderShipping $shipping = null;
}
