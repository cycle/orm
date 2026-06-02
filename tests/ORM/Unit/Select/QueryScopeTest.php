<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Select;

use Cycle\Database\Query\SelectQuery;
use Cycle\ORM\Select\AbstractLoader;
use Cycle\ORM\Select\QueryBuilder;
use Cycle\ORM\Select\QueryScope;
use PHPUnit\Framework\TestCase;

final class QueryScopeTest extends TestCase
{
    public function testAppliesWhereAndOrderBy(): void
    {
        $query = new SelectQuery(['users']);
        $scope = new QueryScope(['status' => 'active'], ['created_at' => 'DESC']);

        $scope->apply($this->makeBuilder($query));

        $tokens = $query->getTokens();
        $this->assertCount(1, $tokens['where']);
        $this->assertSame('users.status', $tokens['where'][0][1][0]);
        $this->assertSame([['users.created_at', 'DESC']], $tokens['orderBy']);
    }

    public function testDoesNotWrapByDefault(): void
    {
        $query = (new SelectQuery(['users']))
            ->where('id', 1)
            ->orWhere('id', 2);

        (new QueryScope(['status' => 'active']))->apply($this->makeBuilder($query));

        // Flat token list: no opening-paren group inserted before the user tokens.
        $where = $query->getTokens()['where'];
        $this->assertNotSame(['AND', '('], $where[0]);
        // Scope condition appended at the top level.
        $this->assertSame('users.status', $where[\count($where) - 1][1][0]);
    }

    public function testWrapWhereEnclosesExistingConditions(): void
    {
        $query = (new SelectQuery(['users']))
            ->where('id', 1)
            ->orWhere('id', 2);

        (new QueryScope(['status' => 'active'], wrapWhere: true))->apply($this->makeBuilder($query));

        $where = $query->getTokens()['where'];

        // User conditions are now enclosed in a leading AND-group...
        $this->assertSame(['AND', '('], $where[0]);
        $this->assertSame(['', ')'], $where[3]);
        // ...with the scope condition appended after the group.
        $this->assertSame('users.status', $where[4][1][0]);
    }

    public function testWrapWhereWithEmptyConditionsStillProtectsUserWhere(): void
    {
        $query = (new SelectQuery(['users']))
            ->where('id', 1)
            ->orWhere('id', 2);

        // Empty $where but wrapWhere: true — should wrap user conditions, add nothing.
        (new QueryScope([], wrapWhere: true))->apply($this->makeBuilder($query));

        $this->assertEquals(
            [
                ['AND', '('],
                ['AND', ['id', '=', $query->getTokens()['where'][1][1][2]]],
                ['OR', ['id', '=', $query->getTokens()['where'][2][1][2]]],
                ['', ')'],
            ],
            $query->getTokens()['where'],
        );
    }

    private function makeBuilder(SelectQuery $query): QueryBuilder
    {
        $loader = $this->createMock(AbstractLoader::class);
        $loader->method('fieldAlias')->willReturn(null);
        $loader->method('getAlias')->willReturn('users');
        $loader->method('getTarget')->willReturn('user');
        $loader->method('getParentLoader')->willReturn(null);

        return new QueryBuilder($query, $loader);
    }
}
