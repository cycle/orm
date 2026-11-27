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
use Testo\Expect;
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

    public function readingUnknownFieldFails(): never
    {
        $factory = new ClasslessProxyFactory();
        $entity = $factory->create($this->relationMap(), 'classless_user', ['id']);

        Expect::exception(\RuntimeException::class)->withMessage(\sprintf('Undefined property %s.name.', $entity::class));

        $entity->name;
    }

    public function debugInfoHidesProxyInternals(): void
    {
        $factory = new ClasslessProxyFactory();
        $entity = $factory->create($this->relationMap(), 'classless_user', ['id']);
        $entity->id = 1;
        $entity->tags = ['tag'];

        $info = $entity->__debugInfo();

        Assert::same($info['id'], 1);
        Assert::same($info["\0" . $entity::class . "\0tags"], ['tag']);
        Assert::false(\array_key_exists('__cycle_orm_rel_map', $info));
        Assert::false(\array_key_exists('__cycle_orm_rel_data', $info));
    }

    public function extractRelationsOfEmptyNonProxyReturnsEmptyArray(): void
    {
        $factory = new ClasslessProxyFactory();

        $relations = $factory->extractRelations($this->relationMap(), new \stdClass());

        Assert::same($relations, []);
    }

    public function extractRelationsOfNonProxyPreservesObjectArrayAndNullValues(): void
    {
        $factory = new ClasslessProxyFactory();
        $post = new \stdClass();
        $entity = new \stdClass();
        $entity->posts = $post;
        $entity->tags = null;

        $relations = $factory->extractRelations($this->relationMap(), $entity);

        Assert::same($relations, ['posts' => $post, 'tags' => null]);
    }

    public function extractRelationsOfNonProxyFiltersNonRelationAndMissingProperties(): void
    {
        $factory = new ClasslessProxyFactory();
        $entity = new \stdClass();
        $entity->id = 1;
        $entity->name = 'foo';
        $entity->posts = ['post'];

        $relations = $factory->extractRelations($this->relationMap(), $entity);

        Assert::same($relations, ['posts' => ['post']]);
    }

    public function extractRelationsOfNonProxyHandlesPrivateAndProtectedProperties(): void
    {
        $factory = new ClasslessProxyFactory();
        $entity = new class {
            public int $id = 1;
            protected array $posts = ['post'];
            private ?string $tags = null;
        };

        $relations = $factory->extractRelations($this->relationMap(), $entity);

        Assert::same($relations, ['posts' => ['post'], 'tags' => null]);
    }

    public function extractDataAndEntityToArrayWorkOnNonProxyObjects(): void
    {
        $factory = new ClasslessProxyFactory();
        $relMap = $this->relationMap();
        $entity = new \stdClass();
        $entity->id = 1;
        $entity->posts = ['post'];

        Assert::same($factory->extractData($relMap, new \stdClass()), []);
        Assert::same($factory->extractData($relMap, $entity), ['id' => 1]);
        Assert::same($factory->entityToArray($entity), ['id' => 1, 'posts' => ['post']]);
    }

    public function entityToArrayPrefersPendingReferenceOverStoredRelationValue(): void
    {
        $factory = new ClasslessProxyFactory();
        $entity = $factory->create($this->relationMap(), 'classless_user', ['id']);
        $entity->id = 1;
        $entity->posts = ['plain'];
        $reference = new Reference('post', ['user_id' => 1]);
        $entity->posts = $reference;
        $entity->tags = ['tag'];

        $data = $factory->entityToArray($entity);

        Assert::same($data, ['posts' => $reference, 'id' => 1, 'tags' => ['tag']]);
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
