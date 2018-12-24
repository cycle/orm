<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Cycle\Select;

use Spiral\Database\DatabaseInterface;

/**
 * Defines the access to the SQL database.
 */
interface SourceInterface
{
    // points to the scope which must be applied to all queries
    public const DEFAULT_CONSTRAIN = '@default';

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
     * Create version of source with newly added/removed constrain.
     *
     * @param string                  $name
     * @param ConstrainInterface|null $constrain
     * @return SourceInterface
     */
    public function withConstrain(string $name, ?ConstrainInterface $constrain): SourceInterface;

    /**
     * Return named query constrain or return null.
     *
     * @param string $name
     * @return ConstrainInterface|null
     */
    public function getConstrain(string $name = self::DEFAULT_CONSTRAIN): ?ConstrainInterface;
}