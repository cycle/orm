<?php

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case408\Type;

use Cycle\ORM\Parser\CastableInterface;

final class TypeCaster implements CastableInterface
{
    /** @var array<non-empty-string, class-string<ValueObjectInterface>> */
    private array $fields = [];

    public function setRules(array $rules): array
    {
        foreach ($rules as $field => $rule) {
            if (\is_string($rule) && \is_subclass_of($rule, ValueObjectInterface::class)) {
                $this->fields[$field] = $rule;
                unset($rules[$field]);
            }
        }

        return $rules;
    }

    public function cast(array $data): array
    {
        foreach ($this->fields as $field => $class) {
            if (!isset($data[$field])) {
                continue;
            }

            /** @see ValueObjectInterface::create() */
            $data[$field] = [$class, 'create']($data[$field]);
        }

        return $data;
    }
}
