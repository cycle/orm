<?php

declare(strict_types=1);

namespace Cycle\ORM\Service\Implementation;

use Cycle\ORM\FactoryInterface;
use Cycle\ORM\Service\SourceProviderInterface;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select\SourceInterface;

/**
 * @internal
 */
final class SourceProvider implements SourceProviderInterface
{
    /** @var SourceInterface[] */
    private array $sources = [];

    public function __construct(
        private FactoryInterface $factory,
        private SchemaInterface $schema,
    ) {}

    /**
     * @param non-empty-string $entity
     */
    public function getSource(string $entity): SourceInterface
    {
        return $this->sources[$entity] ?? ($this->sources[$entity] = $this->factory->source($this->schema, $entity));
    }
}
