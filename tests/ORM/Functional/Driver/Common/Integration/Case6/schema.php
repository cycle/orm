<?php

declare(strict_types=1);

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\SchemaInterface as Schema;
use Cycle\ORM\Select\Repository;
use Cycle\ORM\Select\Source;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case6\Entity\User;

return [
    'users' => [
        Schema::ENTITY => User::class,
        Schema::MAPPER => Mapper::class,
        Schema::SOURCE => Source::class,
        Schema::REPOSITORY => Repository::class,
        Schema::DATABASE => 'default',
        Schema::TABLE => 'users',
        Schema::PRIMARY_KEY => ['id'],
        Schema::FIND_BY_KEYS => ['id'],
        Schema::COLUMNS => [
            'id' => 'id',
            'login' => 'login',
        ],
        Schema::RELATIONS => [],
        Schema::TYPECAST => [
            'id' => 'int',
            'login' => 'string',
        ],
    ],
];
