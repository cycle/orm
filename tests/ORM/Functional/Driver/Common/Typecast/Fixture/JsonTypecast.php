<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Typecast\Fixture;

use Cycle\ORM\Parser\CastableInterface;
use Cycle\ORM\Parser\UncastableInterface;

class JsonTypecast implements CastableInterface, UncastableInterface
{
    private array $rules = [];

    public function setRules(array $rules): array
    {
        foreach ($rules as $key => $rule) {
            if ($rule === 'json') {
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

            $data[$key] = ['json'];
        }

        return $data;
    }

    public function uncast(array $data): array
    {
        foreach ($this->rules as $column => $rule) {
            if (!isset($data[$column])) {
                continue;
            }

            $data[$column] = match ($rule) {
                'json' => 'uncast-json',
                default => (string) $data[$column]
            };
        }

        return $data;
    }
}
