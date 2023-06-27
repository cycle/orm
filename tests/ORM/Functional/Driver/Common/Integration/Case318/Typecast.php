<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case318;

use Cycle\ORM\Parser\CastableInterface;
use Cycle\ORM\Parser\UncastableInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final class Typecast implements CastableInterface, UncastableInterface
{
    private array $rules = [];
    private array $availableRules = ['uuid'];

    public function setRules(array $rules): array
    {
        foreach ($rules as $key => $rule) {
            if (\in_array($rule, $this->availableRules, true)) {
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
            $data[$column] = match ($rule) {
                'uuid' => Uuid::fromString($data[$column]),
                default => $data[$column]
            };
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
                'uuid' => $data[$column] instanceof UuidInterface ? $data[$column]->toString() : $data[$column],
                default => (string) $data[$column]
            };
        }

        return $data;
    }
}
