<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case431\Typecast;

use Cycle\ORM\Parser\CastableInterface;

/**
 * One-way typecast: string → ValueInterfaceUuid (no uncast).
 * Used to test that BulkLoader uses rawValue() over __toString().
 */
class OneWayValueInterfaceTypecast implements CastableInterface
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

            $data[$column] = new ValueInterfaceUuid((string) $data[$column]);
        }

        return $data;
    }
}
