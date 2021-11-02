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

    public function cast(array $values): array
    {
        try {
            foreach ($this->rules as $key => $rule) {
                if (!isset($values[$key])) {
                    continue;
                }
                $values[$key] = $this->castOne($rule, $values[$key]);
            }
        } catch (Throwable $e) {
            throw new TypecastException("Unable to typecast the `$key` field.", $e->getCode(), $e);
        }

        return $values;
    }

    /**
     * @throws \Exception
     */
    private function castOne(mixed $rule, mixed $value): mixed
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
