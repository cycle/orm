<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Cycle\Typecast;

use Spiral\Cycle\Exception\TypecastException;
use Spiral\Database\DatabaseInterface;

interface TypecastInterface
{
    /**
     * Typecast key-values into internal representation according to database schema.
     *
     * @param array             $values
     * @param DatabaseInterface $db
     * @return array
     *
     * @throws TypecastException
     */
    public function cast(array $values, DatabaseInterface $db): array;

    /**
     * Create typecast version with new rule set (column => type) association.
     *
     * @param array $rules
     * @return TypecastInterface
     */
    public function withRules(array $rules): TypecastInterface;
}