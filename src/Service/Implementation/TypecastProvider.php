<?php

declare(strict_types=1);

namespace Cycle\ORM\Service\Implementation;

use Cycle\ORM\FactoryInterface;
use Cycle\ORM\Parser\TypecastInterface;
use Cycle\ORM\Service\SourceProviderInterface;
use Cycle\ORM\Service\TypecastProviderInterface;
use Cycle\ORM\SchemaInterface;

/**
 * @internal
 */
final class TypecastProvider implements TypecastProviderInterface
{
    /** @var array<non-empty-string, TypecastInterface|null> */
    private array $typecasts = [];

    public function __construct(
        private FactoryInterface $factory,
        private SchemaInterface $schema,
        private SourceProviderInterface $sourceProvider,
    ) {}

    public function getTypecast(string $role): ?TypecastInterface
    {
        return \array_key_exists($role, $this->typecasts)
            ? $this->typecasts[$role]
            : ($this->typecasts[$role] = $this->factory->typecast(
                $this->schema,
                $this->sourceProvider->getSource($role)->getDatabase(),
                $role,
            ));
    }
}
