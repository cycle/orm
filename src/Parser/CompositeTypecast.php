<?php

declare(strict_types=1);

namespace Cycle\ORM\Parser;

final class CompositeTypecast implements TypecastInterface
{
    /**
     * @var TypecastInterface[]
     */
    private array $casters;

    public function __construct(TypecastInterface ...$typecasts) {
        $this->casters = $typecasts;
    }

    public function castOne(string $field, mixed $value): mixed
    {
        foreach ($this->casters as $caster) {
            $value = $caster->castOne($field, $value);
        }
        return $value;
    }

    public function castAll(array $values): array
    {
        foreach ($this->casters as $caster) {
            $values = $caster->castAll($values);
        }
        return $values;
    }
}
