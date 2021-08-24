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
     * @deprecated Will be renamed to `withScope` in the Cycle ORM v2.
     * @param ConstrainInterface|null $constrain
     * @return SourceInterface
     */
    public function withConstrain(?ConstrainInterface $constrain): SourceInterface;

    /**
     * @deprecated Will be renamed to `getScope` in the Cycle ORM v2.
     * @return ConstrainInterface|null
     */
    public function getConstrain(): ?ConstrainInterface;
}
