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

    /** @var array<TypecastInterface> */
    private array $typecasts;

    public function __construct(TypecastInterface ...$typecasts)
    {
        $this->typecasts = $typecasts;
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
        foreach ($this->typecasts as $typecast) {
            $rules = $typecast->setRules($rules);
        }

        return $rules;
    }

    public function cast(array $data): array
    {
        foreach ($this->casters as $caster) {
            $data = $caster->cast($data);
        }

        return $data;
    }

    public function uncast(array $data): array
    {
        foreach ($this->uncasters as $uncaster) {
            $data = $uncaster->uncast($data);
        }

        return $data;
    }
}
