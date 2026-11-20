<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures;

use Cycle\ORM\Parser\CastableInterface;

/**
 * UUID typecast that only casts (string -> object) but does NOT uncast (object -> string).
 * Simulates a real-world scenario where a custom typecast handler only implements CastableInterface.
 */
class OneWayUuidTypecast implements CastableInterface
{
    /** @var non-empty-string[] */
    private array $rules = [];

    public function setRules(array $rules): array
    {
        foreach ($rules as $key => $rule) {
            if ($rule === 'uuid') {
                unset($rules[$key]);
                $this->rules[$key] = $rule;
            }
        }

        return $rules;
    }

    public function cast(array $data): array
    {
        foreach ($this->rules as $column => $rule) {
            if (!isset($data[$column])) {
                continue;
            }

            $data[$column] = new UuidPrimaryKey((string) $data[$column]);
        }

        return $data;
    }
}
