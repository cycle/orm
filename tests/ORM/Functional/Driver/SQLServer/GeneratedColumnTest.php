<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer;

// phpcs:ignore
use Cycle\Database\Injection\Fragment;
use Cycle\Database\Schema\AbstractColumn;
use Cycle\ORM\Tests\Functional\Driver\Common\GeneratedColumnTest as CommonClass;
use Ramsey\Uuid\Uuid;

/**
 * @group driver
 * @group driver-sqlserver
 */
class GeneratedColumnTest extends CommonClass
{
    public const DRIVER = 'sqlserver';

    public function createTables(): void
    {
        $this->logger->display();
        $this->getDatabase()->query('DROP SEQUENCE IF EXISTS testSequence1;');
        $this->getDatabase()->query('DROP SEQUENCE IF EXISTS testSequence2;');
        $this->getDatabase()->query('CREATE SEQUENCE testSequence1 START WITH 1;');
        $this->getDatabase()->query('CREATE SEQUENCE testSequence2 START WITH 1;');

        $schema = $this->getDatabase()->table('user')->getSchema();
        $schema->column('id')->type('uuid');
        $schema->column('balance')->type('int')->nullable(false)
            ->defaultValue(new Fragment('NEXT VALUE FOR testSequence1'));
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
        $schema->column('body')->type('int')->nullable(false)
            ->defaultValue(new Fragment('NEXT VALUE FOR testSequence2'));
        $schema->column('created_at')->type('datetime')->nullable(false)->defaultValue(AbstractColumn::DATETIME_NOW);
        $schema->column('updated_at')->type('datetime')->nullable(false);
        $schema->save();
    }
}
