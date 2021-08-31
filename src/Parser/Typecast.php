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

                $values[$key] = match ($rule) {
                    'int' => (int)$values[$key],
                    'bool' => (bool)$values[$key],
                    'float' => (float)$values[$key],
                    'datetime' => new DateTimeImmutable(
                        $values[$key],
                        $this->database->getDriver()->getTimezone()
                    ),
                    default => $rule($values[$key], $this->database),
                };
            }
        } catch (Throwable $e) {
            throw new TypecastException("Unable to typecast `$key`", $e->getCode(), $e);
        }

        return $values;
    }
}
