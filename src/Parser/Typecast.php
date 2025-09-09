<?php

declare(strict_types=1);

namespace Cycle\ORM\Parser;

use BackedEnum;
use Cycle\ORM\Exception\TypecastException;
use Cycle\Database\DatabaseInterface;

/**
 * Default typecasting class for ORM entities.
 *
 * This class handles casting data from database format to PHP types and vice versa.
 *
 * It supports various rule types including:
 *  - Built-in primitives: 'int', 'bool', 'float', 'datetime'.
 *  - JSON encoding/decoding: 'json'.
 *  - Backed enums: Any class implementing BackedEnum
 *  - Callable functions:
 *      - Single-argument static factory: [ClassName::class, 'simple'].
 *      - Callable with database instance: [ClassName::class, 'withDatabase']
 *      - Callable with additional arguments: [ClassName::class, 'customArgs', ['value1', 'value2']].
 *
 *      ```
 *      class ClassName {
 *          public static function simple(mixed $value): mixed { ... }
 *
 *          public static function withDatabase(
 *              mixed $value,
 *              DatabaseInterface $database, // Will be injected automatically
 *          ): mixed { ... }
 *
 *          public static function customArgs(
 *              mixed $value,
 *              string $arg1, // 'value1' will be passed
 *              string $arg2, // 'value2' will be passed
 *          ): mixed { ... }
 *      }
 *      ```
 */
final class Typecast implements CastableInterface, UncastableInterface
{
    private const RULES = ['int', 'bool', 'float', 'datetime', 'json'];

    /** @var array<non-empty-string, \Closure(mixed): mixed> */
    private array $casters = [];

    /** @var array<non-empty-string, \Closure(mixed): mixed> */
    private array $uncasters = [];

    /**
     * @param non-empty-string $role The role of the entity being typecasted
     * @param DatabaseInterface $database The database instance used for typecasting
     */
    public function __construct(
        private string $role,
        private DatabaseInterface $database,
    ) {}

    public function setRules(array $rules): array
    {
        foreach ($rules as $key => $rule) {
            // Static rules
            if (\in_array($rule, self::RULES, true)) {
                $this->casters[$key] = match ($rule) {
                    'int' => static fn(mixed $value): int => (int) $value,
                    'bool' => static fn(mixed $value): bool => (bool) $value,
                    'float' => static fn(mixed $value): float => (float) $value,
                    'datetime' => fn(mixed $value): \DateTimeImmutable => new \DateTimeImmutable(
                        $value,
                        $this->database->getDriver()->getTimezone(),
                    ),
                    'json' => static fn(mixed $value): array => \json_decode(
                        $value,
                        true,
                        512,
                        \JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE,
                    ),
                };

                if ($rule === 'json') {
                    $this->uncasters[$key] = static fn(mixed $value): string => \json_encode(
                        $value,
                        \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE,
                    );
                }
                unset($rules[$key]);
                continue;
            }

            // Backed enum rules
            if (\is_string($rule) && \is_subclass_of($rule, \BackedEnum::class, true)) {
                /** @var class-string<\BackedEnum> $rule */
                $reflection = new \ReflectionEnum($rule);
                $type = (string) $reflection->getBackingType();

                $this->casters[$key] = $type === 'string'
                    // String backed enum
                    ? static fn(mixed $value): ?\BackedEnum => \is_string($value) || \is_numeric($value)
                        ? $rule::tryFrom((string) $value)
                        : null
                    // Int backed enum
                    : static fn(mixed $value): ?\BackedEnum => \is_int($value) || \is_string($value) && \preg_match('/^\\d++$/', $value) === 1
                        ? $rule::tryFrom((int) $value)
                        : null;

                unset($rules[$key]);
                continue;
            }

            // Callable rules
            if (\is_callable($rule)) {
                $closure = \Closure::fromCallable($rule);
                $this->casters[$key] = (new \ReflectionFunction($closure))->getNumberOfParameters() === 1
                    ? $closure
                    : fn(mixed $value): mixed => $closure($value, $this->database);
                unset($rules[$key]);
                continue;
            }

            // Callable rules with arguments
            if (\is_array($rule) && \array_keys($rule) === [0, 1, 2] && \is_callable([$rule[0], $rule[1]])) {
                $closure = \Closure::fromCallable([$rule[0], $rule[1]]);
                $args = $rule[2];
                \is_array($args) or throw new \InvalidArgumentException(
                    "The third argument of typecast rule for the `{$this->role}.{$key}` must be an array of arguments.",
                );
                unset($rules[$key]);

                // The callable accepts DatabaseInterface as second argument
                $reflection = new \ReflectionFunction($closure);
                if ($reflection->getParameters()[1]?->getType()->getName() === DatabaseInterface::class) {
                    $this->casters[$key] = fn(mixed $value): mixed => $closure($value, $this->database, ...$args);
                    continue;
                }

                $this->casters[$key] = static fn(mixed $value): mixed => $closure($value, ...$args);
            }
        }

        return $rules;
    }

    public function cast(array $data): array
    {
        try {
            foreach ($this->casters as $key => $callable) {
                if (isset($data[$key])) {
                    $data[$key] = $callable($data[$key]);
                }
            }
        } catch (\Throwable $e) {
            throw new TypecastException(
                \sprintf('Unable to typecast the `%s.%s` field: %s', $this->role, $key, $e->getMessage()),
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
        try {
            foreach ($this->uncasters as $key => $callable) {
                if (isset($data[$key])) {
                    $data[$key] = $callable($data[$key]);
                }
            }
        } catch (\Throwable $e) {
            throw new TypecastException(
                "Unable to uncast the `{$this->role}.{$key}` field: {$e->getMessage()}",
                $e->getCode(),
                $e,
            );
        }

        return $data;
    }
}
