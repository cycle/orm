<?php

declare(strict_types=1);

namespace Cycle\ORM\Service\Implementation;

use Cycle\ORM\FactoryInterface;
use Cycle\ORM\MapperInterface;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Service\MapperProviderInterface;

/**
 * @internal
 */
final class MapperProvider implements MapperProviderInterface
{
    /** @var array<non-empty-string, MapperInterface> */
    private array $mappers = [];

    public function __construct(
        private ORMInterface $orm,
        private FactoryInterface $factory
    ) {
    }

    public function getMapper(string $entity): MapperInterface
    {
        return $this->mappers[$entity] ?? ($this->mappers[$entity] = $this->factory->mapper($this->orm, $entity));
    }
}
