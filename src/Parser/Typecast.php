<?php

declare(strict_types=1);

namespace Cycle\ORM\Parser;

use BackedEnum;
use Cycle\ORM\Exception\TypecastException;
use DateTimeImmutable;
use Cycle\Database\DatabaseInterface;
use Throwable;

/**
 * @internal
 */
final class Typecast implements CastableInterface
{
    private const RULES = ['int', 'bool', 'float', 'datetime'];

    /** @var array<non-empty-string, bool> */
    private array $callableRules = [];

    /** @var array<string, class-string<BackedEnum>> */
    private array $enumClasses = [];

    /** @var array<non-empty-string, callable|class-string<BackedEnum>|string> */
    private array $rules = [];

    public function __construct(
        private DatabaseInterface $database
    ) {
    }

    public function setRules(array $rules): array
    {
        foreach ($rules as $key => $rule) {
            if (\in_array($rule, self::RULES, true)) {
                $this->rules[$key] = $rule;
                unset($rules[$key]);
            } elseif (\is_string($rule) && \is_subclass_of($rule, BackedEnum::class, true)) {
                $this->enumClasses[$key] = $rule;
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

    public function cast(array $data): array
    {
        try {
            foreach ($this->rules as $key => $rule) {
                if (!isset($data[$key])) {
                    continue;
                }

                if (isset($this->callableRules[$key])) {
                    $data[$key] = $rule($data[$key], $this->database);
                    continue;
                }

                if (isset($this->enumClasses[$key])) {
                    /** @var class-string<BackedEnum> $rule */
                    $data[$key] = $rule::tryFrom($data[$key]);
                    continue;
                }

                $data[$key] = $this->castPrimitive($rule, $data[$key]);
            }
        } catch (Throwable $e) {
            throw new TypecastException(
                \sprintf('Unable to typecast the `%s` field. %s', $key, $e->getMessage()),
                $e->getCode(),
                $e
            );
        }

        return $data;
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
