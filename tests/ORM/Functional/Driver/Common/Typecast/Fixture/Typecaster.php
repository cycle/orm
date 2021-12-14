<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Typecast\Fixture;

use Cycle\ORM\Parser\TypecastInterface;
use Cycle\ORM\SchemaInterface;

final class Typecaster implements TypecastInterface
{
    /**
     * @var array<string, callable|string> $rules
     */
    public array $rules = [];

    public function __construct(
        SchemaInterface $schema,
        public string $role
    ) {
        $class = $schema->define($role, SchemaInterface::ENTITY);
        // Some magic with reflection to prepare callables
        // ...
    }

    public function cast(array $values): array
    {
        // Use prepared callables
        foreach (array_intersect_key($this->rules, $values) as $field => $rule) {
            $values[$field] = $rule($values[$field]);
        }

        return $values;
    }

    /**
     * @param array<string, callable|string> $rules
     */
    public function setRules(array $rules): array
    {
        $this->rules = $rules;

        return $rules;
    }
}
