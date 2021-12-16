<?php

declare(strict_types=1);

namespace Cycle\ORM\Command\Traits;

use Cycle\ORM\MapperInterface;

/**
 * Use mapper for data preparation
 *
 * @internal
 */
trait MapperTrait
{
    private ?MapperInterface $mapper;

    /**
     * @param array $data will be converted to uncasted data and filtered by mapped columns
     *
     * @return array uncasted and mapped data
     */
    private function prepareData(array &$data): array
    {
        if ($this->mapper === null) {
            return $data;
        }
        $data = $this->mapper->uncast($data);
        return $this->mapper->mapColumns($data);
    }
}
