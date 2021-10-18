<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Relation\BelongsTo;

/**
 * @group driver
 * @group driver-postgres
 */
class BelongsToRelationRenamedFieldsTest extends \Cycle\ORM\Tests\Functional\Relation\BelongsTo\BelongsToRelationRenamedFieldsTest
{
    public const DRIVER = 'postgres';
}
