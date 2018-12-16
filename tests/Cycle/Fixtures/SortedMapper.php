<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Tests\Fixtures;


use Spiral\Cycle\Mapper\Mapper;
use Spiral\Cycle\Selector\QueryScope;
use Spiral\Cycle\Selector\ScopeInterface;

// Sort all records by default
class SortedMapper extends Mapper
{
    public function getScope(string $name = self::DEFAULT_SCOPE): ?ScopeInterface
    {
        return new QueryScope([], ['@.id' => 'ASC']);
    }
}