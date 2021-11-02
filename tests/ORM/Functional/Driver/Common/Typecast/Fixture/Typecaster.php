<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Typecast\Fixture;

use Cycle\ORM\ORMInterface;
use Cycle\ORM\Parser\TypecastInterface;
use Cycle\ORM\SchemaInterface;

final class Typecaster implements TypecastInterface
{
    /**
     * @param array<string, callable|string> $rules
     */
    public function __construct(
        ORMInterface $orm,
        public string $role,
        public array $rules = []
    ) {
        $class = $orm->getSchema()->define($role, SchemaInterface::ENTITY);
        // Some magic with reflection to prepare callables
        // ...
    }

    public function cast(array $values): array
    {
        return $this->applyRules($values);
    }

    private function applyRules(array $values): array
    {
        // Use prepared callables
        foreach (array_intersect_key($this->rules, $values) as $field => $rule) {
            $values[$field] = $rule($values[$field]);
        }
        return $values;
    }
}
