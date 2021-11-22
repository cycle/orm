<?php

declare(strict_types=1);

namespace Cycle\ORM\Parser;

/**
 * @internal
 */
final class CompositeTypecast implements TypecastInterface
{
    /** @var TypecastInterface[] */
    private array $casters;

    public function __construct(TypecastInterface ...$typecasts)
    {
        $this->casters = $typecasts;
    }

    public function setRules(array $rules): array
    {
        foreach ($this->casters as $typecast) {
            $rules = $typecast->setRules($rules);
        }

        return $rules;
    }

    public function cast(array $values): array
    {
        foreach ($this->casters as $caster) {
            $values = $caster->cast($values);
        }
        return $values;
    }
}
