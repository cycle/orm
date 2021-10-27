<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Relation\BelongsTo;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\BelongsTo\BelongsToRelationRenamedFieldsTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres
 */
class BelongsToRelationRenamedFieldsTest extends CommonClass
{
    public const DRIVER = 'postgres';
}
