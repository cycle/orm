<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case416\Typecast;

use Cycle\ORM\Parser\CastableInterface;
use Cycle\ORM\Parser\UncastableInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class UuidTypecast implements CastableInterface, UncastableInterface
{
    /** @var non-empty-string[] */
    private array $rules = [];

    public function setRules(array $rules): array
    {
        /**
         * @var non-empty-string $key
         * @var mixed $rule
         */
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

            \assert(\is_string($data[$column]));
            $data[$column] = Uuid::fromString($data[$column]);
        }

        return $data;
    }

    public function uncast(array $data): array
    {
        foreach ($this->rules as $column => $rule) {
            if (!isset($data[$column]) || !$data[$column] instanceof UuidInterface) {
                continue;
            }

            $data[$column] = $data[$column]->toString();
        }

        return $data;
    }
}
