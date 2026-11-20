<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit;

use Cycle\ORM\FactoryInterface;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Relation;
use Cycle\ORM\Relation\RelationInterface;
use Cycle\ORM\RelationMap;
use Cycle\ORM\SchemaInterface;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

#[Test]
final class RelationMapTest
{
    public function childKeepsOwnOuterRelationsAndInheritsParentOnes(): void
    {
        $relMap = $this->build('admin', [
            'admin' => ['post' => ['author' => $this->hasMany('admin')]],
            'user' => ['comment' => ['user' => $this->hasMany('user')]],
        ], ['admin' => 'user']);

        Assert::same(\array_keys($relMap->getMasters()), ['post.author:admin', 'comment.user:user']);
    }

    public function morphedRelationBecomesOneDependency(): void
    {
        $relMap = $this->build('image', [
            'image' => ['post' => ['image' => [
                Relation::TYPE => Relation::MORPHED_HAS_ONE,
                Relation::TARGET => 'image',
                Relation::SCHEMA => [Relation::OUTER_KEY => 'parent_id'],
            ]]],
        ]);

        Assert::same(\array_keys($relMap->getMasters()), ['post.~morphed~image:image']);
    }

    public function childRelationOverridesParentOneOfTheSameName(): void
    {
        $schema = m::mock(SchemaInterface::class);
        $schema->allows('getOuterRelations')->andReturn([]);
        $schema->allows('getInnerRelations')->andReturnUsing(static fn(string $r): array => match ($r) {
            'admin' => ['posts' => []],
            'user' => ['posts' => [], 'comments' => []],
        });
        $schema->allows('define')->andReturnUsing(
            static fn(string $r, int $property): ?string => $property === SchemaInterface::PARENT && $r === 'admin' ? 'user' : null,
        );
        $factory = m::mock(FactoryInterface::class);
        $factory->allows('relation')->andReturnUsing(static function ($orm, $schema, string $role, string $name): RelationInterface {
            $relation = m::mock(RelationInterface::class);
            $relation->allows('getName')->andReturn("$role.$name");
            return $relation;
        });
        $orm = m::mock(ORMInterface::class);
        $orm->allows('getSchema')->andReturn($schema);
        $orm->allows('getFactory')->andReturn($factory);

        $relations = RelationMap::build($orm, 'admin')->getRelations();

        Assert::same(
            \array_map(static fn(RelationInterface $r): string => $r->getName(), $relations),
            ['posts' => 'admin.posts', 'comments' => 'user.comments'],
        );
    }

    private function hasMany(string $target): array
    {
        return [
            Relation::TYPE => Relation::HAS_MANY,
            Relation::TARGET => $target,
            Relation::SCHEMA => [Relation::OUTER_KEY => 'user_id'],
        ];
    }

    /**
     * @param array<string, array> $outer Outer relations by role.
     * @param array<string, string> $parents Parent role by child role.
     */
    private function build(string $role, array $outer, array $parents = []): RelationMap
    {
        $schema = m::mock(SchemaInterface::class);
        $schema->allows('getOuterRelations')->andReturnUsing(static fn(string $r): array => $outer[$r] ?? []);
        $schema->allows('getInnerRelations')->andReturn([]);
        $schema->allows('define')->andReturnUsing(
            static fn(string $r, int $property): ?string => $property === SchemaInterface::PARENT ? $parents[$r] ?? null : null,
        );
        $orm = m::mock(ORMInterface::class);
        $orm->allows('getSchema')->andReturn($schema);
        $orm->allows('getFactory')->andReturn(m::mock(FactoryInterface::class));
        return RelationMap::build($orm, $role);
    }
}
