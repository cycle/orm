<?php

declare(strict_types=1);

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\SchemaInterface as Schema;
use Cycle\ORM\Select\Source;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case398\Entity\FilterProduct;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case398\Entity\Product;

return [
    Product::ROLE => [
        Schema::ENTITY => Product::class,
        Schema::SOURCE => Source::class,
        Schema::DATABASE => 'default',
        Schema::TABLE => 'products',
        Schema::PRIMARY_KEY => ['id'],
        Schema::COLUMNS => [
            'id' => 'id',
            'title' => 'title',
        ],
        Schema::RELATIONS => [],
        Schema::TYPECAST => [
            'id' => 'int',
        ],
        Schema::SCHEMA => [],
    ],
    FilterProduct::ROLE => [
        Schema::ENTITY => FilterProduct::class,
        Schema::SOURCE => Source::class,
        Schema::DATABASE => 'default',
        Schema::MAPPER => Mapper::class,
        Schema::TABLE => 'filter_products_table',
        Schema::PRIMARY_KEY => ['product_id', 'filter_id'],
        Schema::COLUMNS => [
            'id' => 'id',
            'filterId' => 'filter_id',
            'productId' => 'product_id',
        ],
        Schema::RELATIONS => [
        ],
        Schema::TYPECAST => [
            'productId' => 'int',
            'filterId' => 'int',
        ],
        Schema::SCHEMA => [],
    ],
];
