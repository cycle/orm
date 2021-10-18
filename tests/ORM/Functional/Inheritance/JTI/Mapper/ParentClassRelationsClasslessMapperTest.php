<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Inheritance\JTI\Mapper;

use Cycle\ORM\Mapper\ClasslessMapper;

abstract class ParentClassRelationsClasslessMapperTest extends ParentClassRelationsStdMapperTest
{
    protected const DEFAULT_MAPPER = ClasslessMapper::class;
}
