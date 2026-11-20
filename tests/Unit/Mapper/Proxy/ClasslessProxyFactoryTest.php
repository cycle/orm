<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Mapper\Proxy;

use Cycle\ORM\FactoryInterface;
use Cycle\ORM\Mapper\Proxy\ClasslessProxyFactory;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Reference\Reference;
use Cycle\ORM\Relation\ActiveRelationInterface;
use Cycle\ORM\RelationMap;
use Cycle\ORM\SchemaInterface;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

#[Test]
final class ClasslessProxyFactoryTest
{
    public function entitiesOfOneRoleShareTheProxyClass(): void
    {
        $factory = new ClasslessProxyFactory();
        $relMap = $this->relationMap();

        $first = $factory->create($relMap, 'classless_user', ['id', 'name']);
        $second = $factory->create($relMap, 'classless_user', ['id', 'name']);

        Assert::same($second::class, $first::class);
        Assert::true(\property_exists($first, 'name'));
    }

    public function referenceAssignedToRelationIsResolvedOnRead(): void
    {
        $factory = new ClasslessProxyFactory();
        $entity = $factory->create($this->relationMap(), 'classless_user', ['id']);

        $entity->posts = new Reference('post', ['user_id' => 1]);

        Assert::same($entity->posts, ['resolved']);
    }

    public function referenceAssignedAfterValueIsResolvedOnRead(): void
    {
        $factory = new ClasslessProxyFactory();
        $entity = $factory->create($this->relationMap(), 'classless_user', ['id']);
        $entity->posts = ['plain'];

        $entity->posts = new Reference('post', ['user_id' => 1]);

        Assert::same($entity->posts, ['resolved']);
    }

    public function plainRelationValueIsReturnedAsIs(): void
    {
        $factory = new ClasslessProxyFactory();
        $entity = $factory->create($this->relationMap(), 'classless_user', ['id']);

        $entity->posts = ['plain'];

        Assert::same($entity->posts, ['plain']);
    }

    public function extractRelationsReturnsEveryRelation(): void
    {
        $factory = new ClasslessProxyFactory();
        $relMap = $this->relationMap();
        $entity = $factory->create($relMap, 'classless_user', ['id']);
        $reference = new Reference('post', ['user_id' => 1]);
        $entity->id = 1;
        $entity->posts = $reference;
        $entity->tags = ['tag'];

        $relations = $factory->extractRelations($relMap, $entity);

        Assert::same($relations, ['posts' => $reference, 'tags' => ['tag']]);
    }

    public function extractDataSkipsRelations(): void
    {
        $factory = new ClasslessProxyFactory();
        $relMap = $this->relationMap();
        $entity = $factory->create($relMap, 'classless_user', ['id', 'name']);
        $entity->id = 1;
        $entity->name = 'foo';
        $entity->tags = ['tag'];

        Assert::same($factory->extractData($relMap, $entity), ['id' => 1, 'name' => 'foo']);
    }

    private function relationMap(): RelationMap
    {
        $relation = m::mock(ActiveRelationInterface::class);
        $relation->allows('resolve')->andReturn(['loaded']);
        $relation->allows('collect')->with(['loaded'])->andReturn(['resolved']);

        $schema = m::mock(SchemaInterface::class);
        $schema->allows('getOuterRelations')->andReturn([]);
        $schema->allows('getInnerRelations')->andReturn(['posts' => [], 'tags' => []]);
        $schema->allows('define')->andReturn(null);
        $factory = m::mock(FactoryInterface::class);
        $factory->allows('relation')->andReturn($relation);
        $orm = m::mock(ORMInterface::class);
        $orm->allows('getSchema')->andReturn($schema);
        $orm->allows('getFactory')->andReturn($factory);
        return RelationMap::build($orm, 'classless_user');
    }
}
