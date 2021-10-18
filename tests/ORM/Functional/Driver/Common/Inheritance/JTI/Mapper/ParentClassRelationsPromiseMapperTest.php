<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\JTI\Mapper;

use Cycle\ORM\Mapper\PromiseMapper;
use Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\JTI\Relation\ParentClassRelationsTest;

abstract class ParentClassRelationsPromiseMapperTest extends ParentClassRelationsTest
{
    protected const DEFAULT_MAPPER = PromiseMapper::class;
}
