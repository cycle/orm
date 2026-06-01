<?php

declare(strict_types=1);

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Schema\GeneratedField;
use Cycle\ORM\SchemaInterface as Schema;
use Cycle\ORM\Select\Source;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case8\Entity\Event;

return [
    'event' => [
        Schema::ENTITY => Event::class,
        Schema::MAPPER => Mapper::class,
        Schema::SOURCE => Source::class,
        Schema::DATABASE => 'default',
        Schema::TABLE => 'event',
        // Composite PK: application-provided UUID (partition key) + database-generated id.
        // Note: these are *field* names (keys of COLUMNS), not DB column names.
        Schema::PRIMARY_KEY => ['parentId', 'id'],
        Schema::FIND_BY_KEYS => ['parentId', 'id'],
        Schema::COLUMNS => [
            'parentId' => 'parent_id',
            'id' => 'id',
            'payload' => 'payload',
        ],
        Schema::TYPECAST => [
            'parentId' => 'string',
            'id' => 'int',
        ],
        Schema::RELATIONS => [],
        Schema::SCHEMA => [],
        // `id` is produced by the database on insert and fetched back via RETURNING.
        Schema::GENERATED_FIELDS => [
            'id' => GeneratedField::ON_INSERT,
        ],
    ],
];
