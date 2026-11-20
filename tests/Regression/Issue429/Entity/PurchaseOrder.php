<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Regression\Issue429\Entity;

class PurchaseOrder
{
    public ?int $id = null;
    public string $number;

    /** @var iterable<PurchaseOrderItem> */
    public iterable $items = [];

    public function __construct(string $number)
    {
        $this->number = $number;
    }
}
