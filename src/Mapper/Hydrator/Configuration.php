<?php

declare(strict_types=1);

namespace Cycle\ORM\Mapper\Hydrator;

class Configuration extends \GeneratedHydrator\Configuration
{
    public function __construct()
    {
    }

    /**
     * @psalm-param class-string<HydratedClass> $hydratedClassName
     */
    public function setHydratedClassName(string $hydratedClassName): void
    {
        $this->hydratedClassName = $hydratedClassName;
    }
}