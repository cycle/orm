<?php

declare(strict_types=1);

namespace Cycle\ORM\Parser;

/**
 * @internal
 */
final class CompositeTypecast implements CastableInterface, UncastableInterface
{
    /** @var CastableInterface[] */
    private array $casters = [];

    /** @var UncastableInterface[] */
    private array $uncasters = [];

    public function __construct(TypecastInterface ...$typecasts)
    {
        foreach ($typecasts as $caster) {
            if ($caster instanceof CastableInterface) {
                $this->casters[] = $caster;
            }

            if ($caster instanceof UncastableInterface) {
                \array_unshift($this->uncasters, $caster);
            }
        }
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

    public function uncast(array $values): array
    {
        foreach ($this->uncasters as $uncaster) {
            $values = $uncaster->uncast($values);
        }

        return $values;
    }
}
