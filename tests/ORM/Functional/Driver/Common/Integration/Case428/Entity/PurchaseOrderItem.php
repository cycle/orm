<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case428\Entity;

class PurchaseOrderItem
{

    public ?int $id = null;
    public int $purchase_order_id;
    public ?int $order_item_id = null;
    public ?OrderItem $orderItem = null;
    public int $quantity = 1;

    public function __construct(int $quantity = 1)
    {
        $this->quantity = $quantity;
    }

}
