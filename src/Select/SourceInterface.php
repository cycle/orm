<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Select;

use Spiral\Database\DatabaseInterface;

/**
 * Defines the access to the SQL database.
 */
interface SourceInterface
{
    /**
     * Get database associated with the entity.
     *
     * @return DatabaseInterface
     */
    public function getDatabase(): DatabaseInterface;

    /**
     * Get table associated with the entity.
     *
     * @return string
     */
    public function getTable(): string;

    /**
     * Associate query constrain (or remove association).
     *
     * @param ConstrainInterface|null $constrain
     * @return SourceInterface
     */
    public function withConstrain(?ConstrainInterface $constrain): SourceInterface;

    /**
     * Return associated query constrain.
     *
     * @return ConstrainInterface|null
     */
    public function getConstrain(): ?ConstrainInterface;
}
