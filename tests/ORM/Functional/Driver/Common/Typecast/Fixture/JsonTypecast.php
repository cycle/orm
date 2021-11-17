<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Typecast\Fixture;

use Cycle\ORM\Parser\TypecastInterface;

class JsonTypecast implements TypecastInterface
{
    private array $rules = [];

    public function setRules(array &$rules): void
    {
        foreach ($rules as $key => $rule) {
            if ($rule === 'json') {
                unset($rules[$key]);
                $this->rules[$key] = $rule;
            }
        }
    }

    public function cast(array $values): array
    {
        foreach ($this->rules as $key => $rule) {
            if (!isset($values[$key])) {
                continue;
            }

            $values[$key] = 'json';
        }

        return $values;
    }
}
