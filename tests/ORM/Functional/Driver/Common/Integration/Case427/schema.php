<?php

declare(strict_types=1);

use Cycle\ORM\Relation;
use Cycle\ORM\SchemaInterface as Schema;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case427\Entity\Buyer;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case427\Entity\BuyerPartner;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case427\Entity\User;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case427\UuidTypecast;

return [
    'user' => [
        Schema::ENTITY => User::class,
        Schema::TABLE => 'case_5_users',
        Schema::PRIMARY_KEY => ['id'],
        Schema::COLUMNS => [
            'id' => 'id',
            'name' => 'name',
        ],
        Schema::TYPECAST => [
            'id' => 'uuid',
        ],
        Schema::TYPECAST_HANDLER => [UuidTypecast::class, Cycle\ORM\Parser\Typecast::class],
    ],
    'buyer' => [
        Schema::ENTITY => Buyer::class,
        Schema::TABLE => 'case_5_buyers',
        Schema::PRIMARY_KEY => ['id'],
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
                Relation::TARGET => 'user',
                Relation::LOAD => Relation::LOAD_PROMISE,
                Relation::SCHEMA => [
                    Relation::COLLECTION_TYPE => 'array',
                    Relation::THROUGH_ENTITY => 'buyer_partner',
                    Relation::INNER_KEY => 'id',
                    Relation::OUTER_KEY => 'id',
                    Relation::THROUGH_INNER_KEY => 'buyer_id',
                    Relation::THROUGH_OUTER_KEY => 'partner_id',
                ],
            ],
        ],
        Schema::TYPECAST_HANDLER => [UuidTypecast::class, Cycle\ORM\Parser\Typecast::class],
    ],
    'buyer_partner' => [
        Schema::ENTITY => BuyerPartner::class,
        Schema::TABLE => 'case_5_buyer_partners',
        Schema::PRIMARY_KEY => ['buyer_id', 'partner_id'],
        Schema::COLUMNS => [
            'buyer_id' => 'buyer_id',
            'partner_id' => 'partner_id',
        ],
        Schema::TYPECAST => [
            'buyer_id' => 'int',
            'partner_id' => 'uuid',
        ],
        Schema::TYPECAST_HANDLER => [UuidTypecast::class, Cycle\ORM\Parser\Typecast::class],
    ],
];
