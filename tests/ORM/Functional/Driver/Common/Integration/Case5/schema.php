<?php

declare(strict_types=1);

use Cycle\ORM\Relation;
use Cycle\ORM\SchemaInterface as Schema;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case5\Entity\Buyer;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case5\Entity\BuyerPartner;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case5\Entity\User;

return [
    'user' => [
        Schema::ENTITY => User::class,
        Schema::TABLE => 'users',
        Schema::PRIMARY_KEY => ['id'],
        Schema::COLUMNS => [
            'id' => 'id',
            'name' => 'name',
        ],
        Schema::TYPECAST => [
            'id' => 'int',
        ],
    ],
    'buyer' => [
        Schema::ENTITY => Buyer::class,
        Schema::TABLE => 'buyers',
        Schema::PRIMARY_KEY => ['id'],
        Schema::PARENT => 'user',
        Schema::COLUMNS => [
            'id' => 'id',
            'address' => 'address',
        ],
        Schema::TYPECAST => [
            'id' => 'int',
        ],
        Schema::RELATIONS => [
            'partners' => [
                Relation::TYPE => Relation::MANY_TO_MANY,
                Relation::TARGET => 'buyer',
                Relation::LOAD => Relation::LOAD_PROMISE,
                Relation::SCHEMA => [
                    Relation::THROUGH_ENTITY => 'buyer_partner',
                    Relation::INNER_KEY => 'id',
                    Relation::OUTER_KEY => 'id',
                    Relation::THROUGH_INNER_KEY => 'buyer_id',
                    Relation::THROUGH_OUTER_KEY => 'partner_id',
                ],
            ],
        ],
    ],
    'buyer_partner' => [
        Schema::ENTITY => BuyerPartner::class,
        Schema::TABLE => 'buyer_partners',
        Schema::PRIMARY_KEY => ['buyer_id', 'partner_id'],
        Schema::COLUMNS => [
            'buyer_id' => 'buyer_id',
            'partner_id' => 'partner_id',
        ],
        Schema::TYPECAST => [
            'buyer_id' => 'int',
            'partner_id' => 'int',
        ],
    ],
];
