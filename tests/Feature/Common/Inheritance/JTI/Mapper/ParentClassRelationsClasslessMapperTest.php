<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Inheritance\JTI\Mapper;

use Cycle\ORM\Mapper\ClasslessMapper;

class ParentClassRelationsClasslessMapperTest extends ParentClassRelationsStdMapperTest
{
    protected const DEFAULT_MAPPER = ClasslessMapper::class;
}
