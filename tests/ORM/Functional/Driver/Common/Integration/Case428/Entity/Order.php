<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case428\Entity;

class Order
{

    public ?int $id = null;
    public string $number;
    /** @var iterable<OrderItem> */
    public iterable $items = [];

    public function __construct(string $number)
    {
        $this->number = $number;
    }
}
