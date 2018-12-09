<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Entity;

use Spiral\ORM\RepositoryInterface;
use Spiral\ORM\Selector;

/**
 * Source defines SQL specific repository with ability to access object selector and ability to initiate custom sources
 * using query scoping.
 */
interface SourceInterface extends RepositoryInterface
{
    /**
     * @param array $where
     * @return Selector|iterable
     */
    public function find(array $where = []): Selector;

    /**
     * Create new version of repository with scope defined by
     * closure function.
     *
     * @param callable $scope
     * @return self
     */
    public function withScope(callable $scope): self;
}