<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Select;

use Cycle\Database\Query\SelectQuery;
use Cycle\ORM\Select\QueryBuilder;
use Cycle\ORM\Select\QueryScope;
use Cycle\ORM\Tests\Unit\Select\Fixture\StubLoader;

final class QueryScopeTest
{
    #[\Testo\Test]
    public function testAppliesWhereAndOrderBy(): void
    {
        $query = new SelectQuery(['users']);
        $scope = new QueryScope(['status' => 'active'], ['created_at' => 'DESC']);

        $scope->apply($this->makeBuilder($query));

        $tokens = $query->getTokens();
        \Testo\Assert::count($tokens['where'], 1);
        \Testo\Assert::same($tokens['where'][0][1][0], 'users.status');
        \Testo\Assert::same($tokens['orderBy'], [['users.created_at', 'DESC']]);
    }

    #[\Testo\Test]
    public function testDoesNotWrapByDefault(): void
    {
        $query = (new SelectQuery(['users']))
            ->where('id', 1)
            ->orWhere('id', 2);

        (new QueryScope(['status' => 'active']))->apply($this->makeBuilder($query));

        // Flat token list: no opening-paren group inserted before the user tokens.
        $where = $query->getTokens()['where'];
        \Testo\Assert::notSame($where[0], ['AND', '(']);
        // Scope condition appended at the top level.
        \Testo\Assert::same($where[\count($where) - 1][1][0], 'users.status');
    }

    #[\Testo\Test]
    public function testWrapWhereEnclosesExistingConditions(): void
    {
        $query = (new SelectQuery(['users']))
            ->where('id', 1)
            ->orWhere('id', 2);

        (new QueryScope(['status' => 'active'], wrapWhere: true))->apply($this->makeBuilder($query));

        $where = $query->getTokens()['where'];

        // User conditions are now enclosed in a leading AND-group...
        \Testo\Assert::same($where[0], ['AND', '(']);
        \Testo\Assert::same($where[3], ['', ')']);
        // ...with the scope condition appended after the group.
        \Testo\Assert::same($where[4][1][0], 'users.status');
    }

    #[\Testo\Test]
    public function testWrapWhereWithEmptyConditionsStillProtectsUserWhere(): void
    {
        $query = (new SelectQuery(['users']))
            ->where('id', 1)
            ->orWhere('id', 2);

        // Empty $where but wrapWhere: true — should wrap user conditions, add nothing.
        (new QueryScope([], wrapWhere: true))->apply($this->makeBuilder($query));

        \Testo\Assert::equals($query->getTokens()['where'], [
            ['AND', '('],
            ['AND', ['id', '=', $query->getTokens()['where'][1][1][2]]],
            ['OR', ['id', '=', $query->getTokens()['where'][2][1][2]]],
            ['', ')'],
        ]);
    }

    private function makeBuilder(SelectQuery $query): QueryBuilder
    {
        return new QueryBuilder($query, new StubLoader());
    }
}
