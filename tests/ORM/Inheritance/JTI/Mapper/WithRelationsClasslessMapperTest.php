<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Inheritance\JTI\Mapper;

use Cycle\ORM\Mapper\ClasslessMapper;

abstract class WithRelationsClasslessMapperTest extends WithRelationsStdMapperTest
{
    protected const DEFAULT_MAPPER = ClasslessMapper::class;
}
