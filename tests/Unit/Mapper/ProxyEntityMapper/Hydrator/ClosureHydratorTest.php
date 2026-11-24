<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Mapper\ProxyEntityMapper\Hydrator;

use Cycle\ORM\Exception\MapperException;
use Cycle\ORM\FactoryInterface;
use Cycle\ORM\Mapper\Proxy\Hydrator\ClassPropertiesExtractor;
use Cycle\ORM\Mapper\Proxy\Hydrator\ClosureHydrator;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Reference\Reference;
use Cycle\ORM\Relation\ActiveRelationInterface;
use Cycle\ORM\RelationMap;
use Cycle\ORM\SchemaInterface;
use Mockery as m;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Expect;
use Testo\Test;

#[Test]
#[Covers(ClosureHydrator::class)]
final class ClosureHydratorTest
{
    public function privateFieldsAfterMissingOneAreHydrated(): void
    {
        $entity = new HydratedArticle();

        $this->hydrate($entity, ['body' => 'text']);

        Assert::same($entity->values(), [null, 'text', null, null]);
    }

    public function privateRelationsAfterMissingOneAreHydrated(): void
    {
        $entity = new HydratedArticle();

        $this->hydrate($entity, ['reviewer' => 'bob']);

        Assert::same($entity->values(), [null, null, null, 'bob']);
    }

    public function privateRelationsAreHydratedAfterPublicOnes(): void
    {
        $entity = new HydratedArticle();

        $this->hydrate($entity, ['comments' => ['c'], 'author' => 'alice', 'reviewer' => 'bob']);

        Assert::same($entity->comments, ['c']);
        Assert::same($entity->values(), [null, null, 'alice', 'bob']);
    }

    public function privateFieldOfIncompatibleTypeFails(): never
    {
        $entity = new TypedHydratedArticle();

        Expect::exception(MapperException::class)
            ->withMessage("Can't hydrate an entity because property and value types are incompatible.");

        $this->hydrate($entity, ['views' => 'many']);
    }

    public function referenceOfUnionTypedPrivateRelationIsLoaded(): void
    {
        $entity = new TypedHydratedArticle();
        $reference = new Reference('user', ['id' => 1]);
        $author = new HydratedAuthor();
        $relation = m::mock(ActiveRelationInterface::class);
        $relation->expects('resolve')->with($reference, true)->andReturn($author);
        $relation->allows('collect')->with($author)->andReturn($author);

        $this->hydrate($entity, ['author' => $reference], $relation);

        Assert::same($entity->author(), $author);
    }

    private function hydrate(object $entity, array $data, ?ActiveRelationInterface $relation = null): void
    {
        $relations = ['comments', 'author', 'reviewer'];
        $properties = (new ClassPropertiesExtractor())->extract($entity, $relations);

        (new ClosureHydrator())->hydrate($this->relationMap($relations, $relation), $properties, $entity, $data);
    }

    private function relationMap(array $relations, ?ActiveRelationInterface $relation = null): RelationMap
    {
        $schema = m::mock(SchemaInterface::class);
        $schema->allows('getOuterRelations')->andReturn([]);
        $schema->allows('getInnerRelations')->andReturn(\array_fill_keys($relations, []));
        $schema->allows('define')->andReturn(null);
        $factory = m::mock(FactoryInterface::class);
        $factory->allows('relation')->andReturn($relation ?? m::mock(ActiveRelationInterface::class));
        $orm = m::mock(ORMInterface::class);
        $orm->allows('getSchema')->andReturn($schema);
        $orm->allows('getFactory')->andReturn($factory);
        return RelationMap::build($orm, 'article');
    }
}

// phpcs:disable
class HydratedArticle
{
    public $comments;
    private $title;
    private $body;
    private $author;
    private $reviewer;

    public function values(): array
    {
        return [$this->title, $this->body, $this->author, $this->reviewer];
    }
}

class TypedHydratedArticle
{
    public $comments;
    private int $views = 0;
    private HydratedAuthor|string $author = '';
    private $reviewer;

    public function author(): HydratedAuthor|string
    {
        return $this->author;
    }
}

class HydratedAuthor {}
// phpcs:enable
