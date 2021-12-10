<?php

declare(strict_types=1);

namespace Cycle\ORM;

use Cycle\ORM\Registry\IndexProviderInterface;
use Cycle\ORM\Registry\MapperProviderInterface;
use Cycle\ORM\Registry\RelationProviderInterface;
use Cycle\ORM\Registry\RepositoryProviderInterface;
use Cycle\ORM\Registry\TypecastProviderInterface;

interface EntityRegistryInterface extends
    MapperProviderInterface,
    RepositoryProviderInterface,
    TypecastProviderInterface,
    RelationProviderInterface,
    IndexProviderInterface
{
}
