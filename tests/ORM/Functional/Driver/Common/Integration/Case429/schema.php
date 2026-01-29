<?php

declare(strict_types=1);

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema\GeneratedField;
use Cycle\ORM\SchemaInterface as Schema;
use Cycle\ORM\Select\Repository;
use Cycle\ORM\Select\Source;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case429\Entity\Order;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case429\Entity\OrderItem;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case429\Entity\PurchaseOrder;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case429\Entity\PurchaseOrderItem;

return [
    'order' => [
        Schema::ENTITY => Order::class,
        Schema::MAPPER => Mapper::class,
        Schema::SOURCE => Source::class,
        Schema::DATABASE => 'default',
        Schema::TABLE => 'order',
        Schema::PRIMARY_KEY => ['id'],
        Schema::FIND_BY_KEYS => ['id'],
        Schema::COLUMNS => [
            'id' => 'id',
            'number' => 'number',
        ],
        Schema::RELATIONS => [
            'items' => [
                Relation::TYPE => Relation::HAS_MANY,
                Relation::TARGET => 'order_item',
                Relation::COLLECTION_TYPE => 'array',
                Relation::LOAD => Relation::LOAD_PROMISE,
                Relation::SCHEMA => [
                    Relation::CASCADE => true,
                    Relation::NULLABLE => false,
                    Relation::WHERE => [],
                    Relation::ORDER_BY => [],
                    Relation::INNER_KEY => ['id'],
                    Relation::OUTER_KEY => 'order_id',
                ],
            ],
        ],
        Schema::TYPECAST => [
            'id' => 'int',
            'number' => 'string',
        ],
        Schema::SCHEMA => [],
        Schema::GENERATED_FIELDS => [
            'id' => GeneratedField::ON_INSERT, // autoincrement
        ],
    ],
    'order_item' => [
        Schema::ENTITY => OrderItem::class,
        Schema::SOURCE => Source::class,
        Schema::DATABASE => 'default',
        Schema::MAPPER => Mapper::class,
        Schema::TABLE => 'order_item',
        Schema::PRIMARY_KEY => ['id'],
        Schema::FIND_BY_KEYS => ['id'],
        Schema::COLUMNS => [
            'id' => 'id',
            'order_id' => 'order_id',
            'sku' => 'sku',
            'quantity' => 'quantity',
            'purchase_order_id' => 'purchase_order_id',
            'status' => 'status',
        ],
        Schema::RELATIONS => [
            'order' => [
                Relation::TYPE => Relation::BELONGS_TO,
                Relation::TARGET => 'order',
                Relation::LOAD => Relation::LOAD_PROMISE,
                Relation::SCHEMA => [
                    Relation::CASCADE => true,
                    Relation::NULLABLE => false,
                    Relation::INNER_KEY => 'order_id',
                    Relation::OUTER_KEY => ['id'],
                ],
            ],
            'purchaseOrder' => [
                Relation::TYPE => Relation::BELONGS_TO,
                Relation::TARGET => 'purchase_order',
                Relation::LOAD => Relation::LOAD_PROMISE,
                Relation::SCHEMA => [
                    Relation::CASCADE => true,
                    Relation::NULLABLE => true,
                    Relation::INNER_KEY => 'purchase_order_id',
                    Relation::OUTER_KEY => ['id'],
                ],
            ],
        ],
        Schema::TYPECAST => [
            'id' => 'int',
            'order_id' => 'int',
            'sku' => 'string',
            'quantity' => 'int',
            'purchase_order_id' => 'int',
            'status' => 'int',
        ],
        Schema::SCHEMA => [],
        Schema::GENERATED_FIELDS => [
            'id' => GeneratedField::ON_INSERT, // autoincrement
        ],
    ],
    'purchase_order' => [
        Schema::ENTITY => PurchaseOrder::class,
        Schema::MAPPER => Mapper::class,
        Schema::SOURCE => Source::class,
        Schema::REPOSITORY => Repository::class,
        Schema::DATABASE => 'default',
        Schema::TABLE => 'purchase_order',
        Schema::PRIMARY_KEY => ['id'],
        Schema::FIND_BY_KEYS => ['id'],
        Schema::COLUMNS => [
            'id' => 'id',
            'number' => 'number',
        ],
        Schema::RELATIONS => [
            'items' => [
                Relation::TYPE => Relation::HAS_MANY,
                Relation::TARGET => 'purchase_order_item',
                Relation::COLLECTION_TYPE => 'array',
                Relation::LOAD => Relation::LOAD_PROMISE,
                Relation::SCHEMA => [
                    Relation::CASCADE => true,
                    Relation::NULLABLE => false,
                    Relation::WHERE => [],
                    Relation::ORDER_BY => [],
                    Relation::INNER_KEY => ['id'],
                    Relation::OUTER_KEY => 'purchase_order_id',
                ],
            ],
        ],
        Schema::SCOPE => null,
        Schema::TYPECAST => [
            'id' => 'int',
            'number' => 'string',
        ],
        Schema::SCHEMA => [],
        Schema::GENERATED_FIELDS => [
            'id' => GeneratedField::ON_INSERT, // autoincrement
        ],
    ],
    'purchase_order_item' => [
        Schema::ENTITY => PurchaseOrderItem::class,
        Schema::MAPPER => Mapper::class,
        Schema::SOURCE => Source::class,
        Schema::REPOSITORY => Repository::class,
        Schema::DATABASE => 'default',
        Schema::TABLE => 'purchase_order_item',
        Schema::PRIMARY_KEY => ['id'],
        Schema::FIND_BY_KEYS => ['id'],
        Schema::COLUMNS => [
            'id' => 'id',
            'purchase_order_id' => 'purchase_order_id',
            'order_item_id' => 'order_item_id',
            'quantity' => 'quantity',
        ],
        Schema::RELATIONS => [
            'orderItem' => [
                Relation::TYPE => Relation::BELONGS_TO,
                Relation::TARGET => 'order_item',
                Relation::LOAD => Relation::LOAD_PROMISE,
                Relation::SCHEMA => [
                    Relation::CASCADE => true,
                    Relation::NULLABLE => true,
                    Relation::INNER_KEY => 'order_item_id',
                    Relation::OUTER_KEY => ['id'],
                ],
            ],
        ],
        Schema::SCOPE => null,
        Schema::TYPECAST => [
            'id' => 'primary',
            'purchase_order_id' => 'int',
            'order_item_id' => 'int',
            'quantity' => 'int',
        ],
        Schema::SCHEMA => [],
        Schema::GENERATED_FIELDS => [
            'id' => GeneratedField::ON_INSERT, // autoincrement
        ],
    ],
];
