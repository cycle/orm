<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Inheritance\JTI\Mapper;

use Cycle\ORM\Mapper\PromiseMapper;
use Cycle\ORM\Tests\Inheritance\JTI\Relation\ParentClassRelationsTest;

abstract class ParentClassRelationsPromiseMapperTest extends ParentClassRelationsTest
{
    protected const DEFAULT_MAPPER = PromiseMapper::class;
}
