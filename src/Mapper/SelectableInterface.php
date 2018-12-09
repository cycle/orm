<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Mapper;


use Spiral\Database\DatabaseInterface;
use Spiral\ORM\Loader\Scope\ScopeInterface;
use Spiral\ORM\MapperInterface;
use Spiral\ORM\Selector;

interface SelectableInterface extends MapperInterface
{
    // default selection scope
    public const DEFAULT_SCOPE = '';

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
     * Get initial entity selector. Must include applied scope. Must never return existed entity.
     *
     * @return Selector
     */
    public function getSelector(): Selector;

    /**
     * Return named Selector scope or return null.
     *
     * @param string $name
     * @return ScopeInterface|null
     */
    public function getScope(string $name = self::DEFAULT_SCOPE): ?ScopeInterface;
}