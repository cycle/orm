<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Inheritance\STI\Mapper;

use Cycle\ORM\Mapper\PromiseMapper;
use Cycle\ORM\Mapper\StdMapper;
use Cycle\ORM\Tests\Functional\Inheritance\STI\ManyToManyTest;

abstract class ManyToManyPromiseMapperTest extends ManyToManyTest
{
    protected const PARENT_MAPPER = PromiseMapper::class;
    protected const CHILD_MAPPER = StdMapper::class;
}
