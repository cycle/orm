<?php

declare(strict_types=1);

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Schema\GeneratedField;
use Cycle\ORM\SchemaInterface as Schema;
use Cycle\ORM\Select\Source;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case321\Entity\User1;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case321\Entity\User2;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case321\Entity\User3;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case321\Entity\User4;

return [
    'user1' => [
        Schema::ENTITY => User1::class,
        Schema::MAPPER => Mapper::class,
        Schema::SOURCE => Source::class,
        Schema::DATABASE => 'default',
        Schema::TABLE => 'user1',
        Schema::PRIMARY_KEY => ['id'],
        Schema::FIND_BY_KEYS => ['id'],
        Schema::COLUMNS => [
            'id' => 'id',
        ],
        Schema::RELATIONS => [],
        Schema::SCOPE => null,
        Schema::TYPECAST => [
            'id' => 'int',
        ],
        Schema::SCHEMA => [],
        Schema::GENERATED_FIELDS => [
            'id' => GeneratedField::ON_INSERT, // autoincrement
        ],
    ],
    'user2' => [
        Schema::ENTITY => User2::class,
        Schema::MAPPER => Mapper::class,
        Schema::SOURCE => Source::class,
        Schema::DATABASE => 'default',
        Schema::TABLE => 'user2',
        Schema::PRIMARY_KEY => ['id'],
        Schema::FIND_BY_KEYS => ['id'],
        Schema::COLUMNS => [
            'id' => 'id',
        ],
        Schema::RELATIONS => [],
        Schema::SCOPE => null,
        Schema::TYPECAST => [
            'id' => 'int',
        ],
        Schema::SCHEMA => [],
        Schema::GENERATED_FIELDS => [
            'id' => GeneratedField::ON_INSERT, // autoincrement
        ],
    ],
    'user3' => [
        Schema::ENTITY => User3::class,
        Schema::MAPPER => Mapper::class,
        Schema::SOURCE => Source::class,
        Schema::DATABASE => 'default',
        Schema::TABLE => 'user3',
        Schema::PRIMARY_KEY => ['id'],
        Schema::FIND_BY_KEYS => ['id'],
        Schema::COLUMNS => [
            'id' => 'id',
        ],
        Schema::RELATIONS => [],
        Schema::SCOPE => null,
        Schema::TYPECAST => [
            'id' => 'int',
        ],
        Schema::SCHEMA => [],
        Schema::GENERATED_FIELDS => [],
    ],
    'user4' => [
        Schema::ENTITY => User4::class,
        Schema::MAPPER => Mapper::class,
        Schema::SOURCE => Source::class,
        Schema::DATABASE => 'default',
        Schema::TABLE => 'user4',
        Schema::PRIMARY_KEY => ['id'],
        Schema::COLUMNS => [
            'id' => 'id',
            'counter' => 'counter',
        ],
        Schema::TYPECAST => [
            'id' => 'int',
            'counter' => 'counter',
        ],
        Schema::GENERATED_FIELDS => [
            'counter' => GeneratedField::BEFORE_UPDATE,
        ],
    ],
];
