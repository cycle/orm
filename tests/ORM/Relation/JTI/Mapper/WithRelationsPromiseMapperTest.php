<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Relation\JTI\Mapper;

use Cycle\ORM\Mapper\PromiseMapper;
use Cycle\ORM\Tests\Relation\JTI\WithRelationsTest;

abstract class WithRelationsPromiseMapperTest extends WithRelationsTest
{
    protected const DEFAULT_MAPPER = PromiseMapper::class;
}
