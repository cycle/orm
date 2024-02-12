<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres;

// phpcs:ignore
use Cycle\Database\Schema\AbstractColumn;
use Cycle\ORM\Tests\Functional\Driver\Common\GeneratedColumnTest as CommonClass;
use Ramsey\Uuid\Uuid;

/**
 * @group driver
 * @group driver-postgres
 */
class GeneratedColumnTest extends CommonClass
{
    public const DRIVER = 'postgres';

    public function createTables(): void
    {
        $schema = $this->getDatabase()->table('user')->getSchema();
        $schema->column('id')->type('uuid');
        $schema->column('balance')->type('serial')->nullable(false);
        $schema->save();

        $this->getDatabase()->table('user')->insertMultiple(
            ['id'],
            [
                [Uuid::uuid4()->toString()],
                [Uuid::uuid4()->toString()],
                [Uuid::uuid4()->toString()],
            ]
        );

        $schema = $this->getDatabase()->table('document')->getSchema();
        $schema->column('id')->primary();
        $schema->column('body')->type('serial')->nullable(false);
        $schema->column('created_at')->type('datetime')->nullable(false)->defaultValue(AbstractColumn::DATETIME_NOW);
        $schema->column('updated_at')->type('datetime')->nullable(false);
        $schema->save();
    }
}
