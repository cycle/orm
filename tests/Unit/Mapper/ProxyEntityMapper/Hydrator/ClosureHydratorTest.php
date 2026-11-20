<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Mapper\ProxyEntityMapper\Hydrator;

use Cycle\ORM\FactoryInterface;
use Cycle\ORM\Mapper\Proxy\Hydrator\ClassPropertiesExtractor;
use Cycle\ORM\Mapper\Proxy\Hydrator\ClosureHydrator;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Relation\ActiveRelationInterface;
use Cycle\ORM\RelationMap;
use Cycle\ORM\SchemaInterface;
use Mockery as m;
use Testo\Assert;
use Testo\Codecov\Covers;
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

    private function hydrate(object $entity, array $data): void
    {
        $relations = ['comments', 'author', 'reviewer'];
        $properties = (new ClassPropertiesExtractor())->extract($entity, $relations);

        (new ClosureHydrator())->hydrate($this->relationMap($relations), $properties, $entity, $data);
    }

    private function relationMap(array $relations): RelationMap
    {
        $schema = m::mock(SchemaInterface::class);
        $schema->allows('getOuterRelations')->andReturn([]);
        $schema->allows('getInnerRelations')->andReturn(\array_fill_keys($relations, []));
        $schema->allows('define')->andReturn(null);
        $factory = m::mock(FactoryInterface::class);
        $factory->allows('relation')->andReturn(m::mock(ActiveRelationInterface::class));
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
// phpcs:enable
