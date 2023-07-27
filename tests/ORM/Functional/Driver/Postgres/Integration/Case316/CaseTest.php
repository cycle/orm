<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Integration\Case316;

// phpcs:ignore
use Cycle\Database\TableInterface;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case316\CaseTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres
 */
class CaseTest extends CommonClass
{
    public const DRIVER = 'postgres';

    protected function makeTables(): void
    {
        parent::makeTables();
        // /** @var TableInterface $table */
        // $this->getDatabase()->table('post')->getSchema()->column('content')->type('bytea');
        // $this->getDatabase()->table('comment')->getSchema()->column('content')->type('bytea');
    }
}
