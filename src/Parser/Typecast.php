<?php

declare(strict_types=1);

namespace Cycle\ORM\Parser;

use Cycle\ORM\Exception\TypecastException;
use DateTimeImmutable;
use Cycle\Database\DatabaseInterface;
use Throwable;

final class Typecast implements TypecastInterface
{
    private const RULES = ['int', 'bool', 'float', 'datetime'];

    /** @var array<non-empty-string, bool> */
    private array $callableRules = [];

    /** @var array<non-empty-string, mixed> */
    private array $rules = [];

    public function __construct(
        private DatabaseInterface $database
    ) {
    }

    public function setRules(array $rules): array
    {
        foreach ($rules as $key => $rule) {
            if (in_array($rule, self::RULES, true)) {
                $this->rules[$key] = $rule;
                unset($rules[$key]);
            } elseif (\is_callable($rule)) {
                $this->callableRules[$key] = true;
                $this->rules[$key] = $rule;
                unset($rules[$key]);
            }
        }

        return $rules;
    }

    public function cast(array $values): array
    {
        try {
            foreach ($this->rules as $key => $rule) {
                if (!isset($values[$key])) {
                    continue;
                }

                if (isset($this->callableRules[$key])) {
                    $values[$key] = $rule($values[$key], $this->database);
                    continue;
                }

                $values[$key] = $this->castPrimitive($rule, $values[$key]);
            }
        } catch (Throwable $e) {
            throw new TypecastException(
                sprintf('Unable to typecast the `%s` field. %s', $key, $e->getMessage()),
                $e->getCode(),
                $e
            );
        }

        return $values;
    }

    /**
     * @throws \Exception
     */
    private function castPrimitive(mixed $rule, mixed $value): mixed
    {
        return match ($rule) {
            'int' => (int)$value,
            'bool' => (bool)$value,
            'float' => (float)$value,
            'datetime' => new DateTimeImmutable(
                $value,
                $this->database->getDriver()->getTimezone()
            ),
            default => $value,
        };
    }
}
