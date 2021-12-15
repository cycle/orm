<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Typecast\Fixture;

use Cycle\ORM\Parser\CastableInterface;

class UuidTypecast implements CastableInterface
{
    private array $rules = [];

    public function setRules(array $rules): array
    {
        if (count($rules) > 1) {
            throw new \Exception('UuidTypecast contains more than 1 rule');
        }

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
        foreach ($this->rules as $key => $rule) {
            if (!isset($data[$key])) {
                continue;
            }

            $data[$key] = 'uuid';
        }

        return $data;
    }
}
