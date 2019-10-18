<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Parser;

use Cycle\ORM\Exception\TypecastException;
use Spiral\Database\DatabaseInterface;

final class Typecast implements TypecastInterface
{
    /** @var array */
    private $rules;

    /** @var DatabaseInterface */
    private $database;

    /**
     * @param array             $rules
     * @param DatabaseInterface $database
     */
    public function __construct(array $rules, DatabaseInterface $database)
    {
        $this->rules = $rules;
        $this->database = $database;
    }

    /**
     * @inheritdoc
     */
    public function cast(array $values): array
    {
        try {
            foreach ($this->rules as $key => $rule) {
                if (!array_key_exists($key, $values) || is_null($values[$key])) {
                    continue;
                }

                $values[$key] = $this->invoke($rule, $values[$key]);
            }
        } catch (\Throwable $e) {
            throw new TypecastException("Unable to typecast `$key`", $e->getCode(), $e);
        }

        return $values;
    }

    /**
     * @param mixed $value
     * @return int
     */
    public static function int($value): int
    {
        return intval($value);
    }

    /**
     * @param mixed $value
     * @return float
     */
    public static function float($value): float
    {
        return floatval($value);
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public static function bool($value): bool
    {
        return boolval($value);
    }

    /**
     * Typecast value into datetime.
     *
     * @param string|int        $value
     * @param DatabaseInterface $db
     * @return null|\DateTimeInterface
     *
     * @throws \Exception
     */
    public static function datetime($value, DatabaseInterface $db): ?\DateTimeInterface
    {
        return new \DateTimeImmutable($value, $db->getDriver()->getTimezone());
    }

    /**
     * @param callable $rule
     * @param mixed    $value
     * @return mixed
     */
    private function invoke($rule, $value)
    {
        if (is_string($rule) && method_exists($this, $rule)) {
            // default rules
            $rule = [self::class, $rule];
        }

        return call_user_func($rule, $value, $this->database);
    }
}
