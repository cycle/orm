<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Select\Fixture;

use Cycle\Database\Query\SelectQuery;
use Cycle\ORM\Parser\AbstractNode;
use Cycle\ORM\Select\AbstractLoader;
use Cycle\ORM\Select\LoaderInterface;

/**
 * A root loader of the `user` role aliased `users`, enough for a {@see \Cycle\ORM\Select\QueryBuilder}.
 *
 * Hand-written because Mockery cannot mock the final `AbstractLoader::__destruct()`.
 */
final class StubLoader extends AbstractLoader
{
    public function __construct() {}

    public function isLoaded(): bool
    {
        return false;
    }

    public function getAlias(): string
    {
        return 'users';
    }

    public function fieldAlias(string $field): ?string
    {
        return null;
    }

    public function getTarget(): string
    {
        return 'user';
    }

    public function getParentLoader(): ?LoaderInterface
    {
        return null;
    }

    protected function initNode(): AbstractNode
    {
        throw new \LogicException('Not used by the query builder.');
    }

    protected function applyScope(SelectQuery $query): SelectQuery
    {
        return $query;
    }
}
