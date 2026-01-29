<?php

declare(strict_types=1);

namespace Cycle\ORM\Service\Implementation;

use Cycle\ORM\Exception\ORMException;
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
        private ?ORMInterface $orm,
        private FactoryInterface $factory,
    ) {}

    public function getMapper(string $entity): MapperInterface
    {
        if (isset($this->mappers[$entity])) {
            return $this->mappers[$entity];
        }
        if ($this->orm === null) {
            throw new ORMException('Mapper is not prepared.');
        }

        return $this->mappers[$entity] = $this->factory->mapper($this->orm, $entity);
    }

    public function prepareMappers(): void
    {
        if ($this->orm === null) {
            return;
        }
        foreach ($this->orm->getSchema()->getRoles() as $role) {
            $this->getMapper($role);
        }
        $this->orm = null;
        unset($this->factory);
    }
}
