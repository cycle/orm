<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Regression\Issue429;

use Cycle\ORM\Select;
use Cycle\ORM\Tests\Regression\Issue429\Entity\Order;
use Cycle\ORM\Tests\Regression\Issue429\Entity\PurchaseOrder;
use Cycle\ORM\Tests\Regression\Issue429\Entity\PurchaseOrderItem;
use Cycle\Testing\ORM\OrmTestCase;
use Testo\Assert;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

final class CaseTest extends OrmTestCase
{
    #[Test]
    public function testSelect(): void
    {
        // Order 1
        $order = (new Select($this->orm, Order::class))
            ->load('items')
            ->wherePK(1)
            ->fetchOne();

        // Check result
        Assert::instanceOf($order, Order::class);
        Assert::count($order->items, 2);

        // Order 2
        $order = (new Select($this->orm, Order::class))
            ->load('items')
            ->wherePK(2)
            ->fetchOne();

        // Check result
        Assert::instanceOf($order, Order::class);
        Assert::count($order->items, 1);
    }

    #[Test]
    public function testSave(): void
    {
        /** @var Order $order */
        $order = (new Select($this->orm, Order::class))
            ->wherePK(1)
            ->load('items')
            ->fetchOne();

        $purchaseOrder = new PurchaseOrder('PO001');
        $orderItems = [];
        foreach ($order->items as $orderItem) {
            $purchaseOrderItem = new PurchaseOrderItem($orderItem->quantity);
            $purchaseOrderItem->orderItem = $orderItem;
            $purchaseOrder->items[] = $purchaseOrderItem;
            $orderItem->purchaseOrder = $purchaseOrder;
            $orderItem->status = 1;
            $orderItems[] = $orderItem;
        }

        $this->save($purchaseOrder, ...$orderItems);
    }

    #[Test]
    public function testCreate(): void
    {
        $order = new Order('O0101');
        $purchaseOrder = new PurchaseOrder('PO101');

        $orderItems = [];
        for ($i = 0; $i < 20; $i++) {
            $orderItem = new Entity\OrderItem('A' . $i, 1);
            $orderItem->order = $order;
            // $order->items[] = $orderItem;
            $orderItem->purchaseOrder = $purchaseOrder;
            $orderItems[] = $orderItem;
        }

        $this->save($order, $purchaseOrder, ...$orderItems);
    }

    #[BeforeTest]
    public function setUp(): void
    {
        // Init DB
        $this->makeTables();
        $this->fillData();

        $this->loadSchema(__DIR__ . '/schema.php');
    }

    private function makeTables(): void
    {
        // Make tables
        $this->makeTable('order', [
            'id' => 'primary', // autoincrement
            'number' => 'string',
        ]);

        $this->makeTable('order_item', [
            'id' => 'primary',
            'order_id' => 'int',
            'sku' => 'string',
            'quantity' => 'int',
            'purchase_order_id' => 'int,nullable',
            'status' => 'int',
        ]);

        $this->makeTable('purchase_order', [
            'id' => 'primary',
            'number' => 'string',
        ]);

        $this->makeTable('purchase_order_item', [
            'id' => 'primary',
            'purchase_order_id' => 'int',
            'order_item_id' => 'int,nullable',
            'quantity' => 'int',
        ]);
    }

    private function fillData(): void
    {
        $this->getDatabase()->table('order')->insertMultiple(
            ['number'],
            [
                ['O001'],
                ['O002'],
                ['O003'],
            ],
        );
        $this->getDatabase()->table('order_item')->insertMultiple(
            ['order_id', 'sku', 'quantity', 'purchase_order_id', 'status'],
            [
                [1, 'A', 1, null, 0],
                [1, 'B', 1, null, 0],
                [2, 'A', 2, null, 0],
                [3, 'B', 2, null, 0],
            ],
        );
    }
}
