<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case429\Entity;

class OrderItem
{
    public ?int $id = null;

    public string $sku;
    public int $quantity = 1;
    public int $status = 0;

    public ?int $purchase_order_id = null;
    public ?PurchaseOrder $purchaseOrder = null;

    public int $order_id;
    public Order $order;

    public function __construct(string $sku, int $quantity = 1)
    {
        $this->sku = $sku;
        $this->quantity = $quantity;
    }
}
