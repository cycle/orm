<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case429\Entity;

class PurchaseOrderItem
{
    public ?int $id = null;
    public int $quantity = 1;

    public int $purchase_order_id;
    public PurchaseOrder $purchaseOrder;

    public ?int $order_item_id = null;
    public ?OrderItem $orderItem = null;

    public function __construct(int $quantity = 1)
    {
        $this->quantity = $quantity;
    }
}
