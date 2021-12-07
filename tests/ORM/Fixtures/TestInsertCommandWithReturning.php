<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures;

use Cycle\Database\Query\InsertQuery;

abstract class TestInsertCommandWithReturning extends InsertQuery implements \Cycle\Database\Query\ReturningInterface
{
}
