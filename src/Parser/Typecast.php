<?php

declare(strict_types=1);

namespace Cycle\ORM\Parser;

use Cycle\ORM\Exception\TypecastException;
use Cycle\Database\DatabaseInterface;

final class Typecast implements CastableInterface, UncastableInterface
{
    private const RULES = ['int', 'bool', 'float', 'datetime', 'json'];

    /** @var array<non-empty-string, bool> */
    private array $callableRules = [];

    /** @var array<non-empty-string, array> */
    private array $callableArguments = [];

    /** @var array<string, class-string<\BackedEnum>> */
    private array $enumClasses = [];

    /** @var array<non-empty-string, array|callable|class-string<\BackedEnum>|string> */
    private array $rules = [];

    public function __construct(
        private DatabaseInterface $database,
    ) {}

    public function setRules(array $rules): array
    {
        foreach ($rules as $key => $rule) {
            if (\in_array($rule, self::RULES, true)) {
                $this->rules[$key] = $rule;
                unset($rules[$key]);
            } elseif (\is_string($rule) && \is_subclass_of($rule, \BackedEnum::class, true)) {
                $reflection = new \ReflectionEnum($rule);
                $this->enumClasses[$key] = (string) $reflection->getBackingType();
                $this->rules[$key] = $rule;
                unset($rules[$key]);
            } elseif (
                \is_array($rule)
                && \count($rule) >= 2
                && \is_string($rule[0])
                && \is_string($rule[1])
                && \class_exists($rule[0])
                && \method_exists($rule[0], $rule[1])
            ) {
                if (isset($rule[2]) && \is_array($rule[2])) {
                    $this->callableArguments[$key] = $rule[2];
                }
                $this->callableRules[$key] = true;
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
                    /** @var callable $callable */
                    $callable = \is_array($rule) ? [$rule[0], $rule[1]] : $rule;
                    $arguments = [$data[$key], $this->database, $this->callableArguments[$key] ?? []];
                    $data[$key] = $callable(...$arguments);
                    continue;
                }

                if (isset($this->enumClasses[$key])) {
                    /** @var class-string<\BackedEnum> $rule */
                    $type = $this->enumClasses[$key];
                    $value = $data[$key];
                    $data[$key] = match (true) {
                        !\is_scalar($value) => null,
                        $type === 'string' && (\is_string($type) || \is_numeric($value))
                            => $rule::tryFrom((string) $value),
                        $type === 'int' && (\is_int($value) || \preg_match('/^\\d++$/', $value) === 1)
                            => $rule::tryFrom((int) $value),
                        default => null,
                    };
                    continue;
                }

                $data[$key] = $this->castPrimitive($rule, $data[$key]);
            }
        } catch (\Throwable $e) {
            throw new TypecastException(
                \sprintf('Unable to typecast the `%s` field. %s', $key, $e->getMessage()),
                $e->getCode(),
                $e,
            );
        }

        return $data;
    }

    /**
     * @throws \JsonException
     */
    public function uncast(array $data): array
    {
        foreach ($this->rules as $column => $rule) {
            if (!isset($data[$column])) {
                continue;
            }

            $data[$column] = match ($rule) {
                'json' => \json_encode(
                    $data[$column],
                    \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE,
                ),
                default => $data[$column],
            };
        }

        return $data;
    }

    /**
     * @throws \Exception
     */
    private function castPrimitive(mixed $rule, mixed $value): mixed
    {
        return match ($rule) {
            'int' => (int) $value,
            'bool' => (bool) $value,
            'float' => (float) $value,
            'datetime' => new \DateTimeImmutable(
                $value,
                $this->database->getDriver()->getTimezone(),
            ),
            'json' => \json_decode($value, true, 512, \JSON_THROW_ON_ERROR),
            default => $value,
        };
    }
}
