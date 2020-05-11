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
use DateTimeImmutable;
use Spiral\Database\DatabaseInterface;
use Throwable;

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
                if (!isset($values[$key])) {
                    continue;
                }

                switch ($rule) {
                    case 'int':
                        $values[$key] = (int) $values[$key];
                        break;
                    case 'bool':
                        $values[$key] = (bool) $values[$key];
                        break;
                    case 'float':
                        $values[$key] = (float) $values[$key];
                        break;
                    case 'datetime':
                        $values[$key] = new DateTimeImmutable(
                            $values[$key],
                            $this->database->getDriver()->getTimezone()
                        );
                        break;
                    default:
                        $values[$key] = $rule($values[$key], $this->database);
                }
            }
        } catch (Throwable $e) {
            throw new TypecastException("Unable to typecast `$key`", $e->getCode(), $e);
        }

        return $values;
    }
}
