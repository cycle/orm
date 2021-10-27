<?php

declare(strict_types=1);

namespace Cycle\ORM\Parser;

use Cycle\ORM\Exception\TypecastException;
use DateTimeImmutable;
use Cycle\Database\DatabaseInterface;
use Throwable;

final class Typecast implements TypecastInterface
{
    public function __construct(
        private array $rules,
        private DatabaseInterface $database
    ) {
    }

    public function castOne(string $field, mixed $value): mixed
    {
        if (!isset($this->rules[$field])) {
            return $value;
        }
        try {
            return $this->cast($this->rules[$field], $value);
        } catch (Throwable $e) {
            throw new TypecastException("Unable to typecast `$field`.", $e->getCode(), $e);
        }
    }

    public function castAll(array $values): array
    {
        try {
            foreach ($this->rules as $key => $rule) {
                if (!isset($values[$key])) {
                    continue;
                }
                $values[$key] = $this->cast($rule, $values[$key]);
            }
        } catch (Throwable $e) {
            throw new TypecastException("Unable to typecast the `$key` field.", $e->getCode(), $e);
        }

        return $values;
    }

    /**
     * @throws \Exception
     */
    private function cast(mixed $rule, mixed $value): mixed
    {
        return match ($rule) {
            'int' => (int)$value,
            'bool' => (bool)$value,
            'float' => (float)$value,
            'datetime' => new DateTimeImmutable(
                $value,
                $this->database->getDriver()->getTimezone()
            ),
            default => $rule($value, $this->database),
        };
    }
}
