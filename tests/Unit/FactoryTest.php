<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit;

use Cycle\Database\DatabaseInterface;
use Cycle\Database\DatabaseProviderInterface;
use Cycle\ORM\Collection\ArrayCollectionFactory;
use Cycle\ORM\Collection\IlluminateCollectionFactory;
use Cycle\ORM\Config\RelationConfig;
use Cycle\ORM\Exception\ConfigException;
use Cycle\ORM\Exception\TypecastException;
use Cycle\ORM\Factory;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Parser\TypecastInterface;
use Cycle\ORM\Relation;
use Cycle\ORM\Relation\RelationInterface;
use Cycle\ORM\RepositoryInterface;
use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select\SourceInterface;
use Cycle\ORM\Service\SourceProviderInterface;
use Cycle\ORM\Tests\Fixtures\NotDeletedScope;
use Cycle\ORM\Tests\Fixtures\SortByIDScope;
use Cycle\ORM\Tests\Util\SimpleFactory;
use Mockery as m;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Expect;
use Testo\Test;

#[Test]
#[Covers(Factory::class)]
#[Covers(RelationConfig::class)]
final class FactoryTest
{
    public function customRelationConfigIsUsed(): never
    {
        $factory = new Factory($this->dbal(), new RelationConfig([]));

        Expect::exception(ConfigException::class)->withMessage('Unable to get relation loader `10`.');

        $factory->loader(
            new Schema(['user' => [SchemaInterface::RELATIONS => ['profile' => $this->hasOne()]]]),
            m::mock(SourceProviderInterface::class),
            'user',
            'profile',
        );
    }

    public function unknownRelationTypeIsRejected(): never
    {
        $factory = new Factory($this->dbal(), new RelationConfig([]));

        Expect::exception(ConfigException::class)->withMessage('Unable to get relation `10`.');

        $factory->relation(
            m::mock(ORMInterface::class),
            new Schema(['user' => [SchemaInterface::RELATIONS => ['profile' => $this->hasOne()]]]),
            'user',
            'profile',
        );
    }

    public function relationReceivesLoadAndCollectionOptions(): void
    {
        $calls = [];
        $relation = m::mock(RelationInterface::class);
        $factory = new Factory($this->dbal(), null, $this->recording($calls, $relation));
        $schema = new Schema(['user' => [SchemaInterface::RELATIONS => [
            'profile' => $this->hasOne() + [Relation::LOAD => Relation::LOAD_EAGER, Relation::COLLECTION_TYPE => 'array'],
        ]]]);

        $result = $factory->relation(m::mock(ORMInterface::class), $schema, 'user', 'profile');

        Assert::instanceOf($result, RelationInterface::class);
        Assert::same($calls[0][1]['schema'], [
            Relation::INNER_KEY => 'id',
            Relation::OUTER_KEY => 'user_id',
            Relation::LOAD => Relation::LOAD_EAGER,
            Relation::COLLECTION_TYPE => 'array',
        ]);
    }

    public function schemaTypecastHandlerOverridesDefault(): void
    {
        $default = $this->typecast();
        $own = $this->typecast();
        $factory = (new Factory($this->dbal()))->withDefaultSchemaClasses([SchemaInterface::TYPECAST_HANDLER => $default]);
        $schema = new Schema([
            'user' => [SchemaInterface::TYPECAST_HANDLER => $own],
            'post' => [],
        ]);

        Assert::same($factory->typecast($schema, m::mock(DatabaseInterface::class), 'user'), $own);
        Assert::same($factory->typecast($schema, m::mock(DatabaseInterface::class), 'post'), $default);
    }

    public function repositoryClassMustImplementInterface(): never
    {
        $factory = new Factory($this->dbal());
        $schema = new Schema(['user' => [SchemaInterface::REPOSITORY => \stdClass::class]]);

        Expect::exception(TypecastException::class)
            ->withMessage(\stdClass::class . ' does not implement ' . RepositoryInterface::class);

        $factory->repository(m::mock(ORMInterface::class), $schema, 'user', null);
    }

    public function sourceClassMustImplementInterface(): never
    {
        $factory = new Factory($this->dbal());
        $schema = new Schema(['user' => [SchemaInterface::SOURCE => \stdClass::class]]);

        Expect::exception(TypecastException::class)
            ->withMessage(\stdClass::class . ' does not implement ' . SourceInterface::class);

        $factory->source($schema, 'user');
    }

    public function customSourceReceivesRoleTableAndDatabase(): void
    {
        $calls = [];
        $source = m::mock(SourceInterface::class);
        $sourceClass = $source::class;
        $factory = new Factory($this->dbal(), null, $this->recording($calls, $source));
        $schema = new Schema(['user' => [SchemaInterface::SOURCE => $sourceClass, SchemaInterface::TABLE => 'users']]);

        $result = $factory->source($schema, 'user');

        Assert::instanceOf($result, SourceInterface::class);
        Assert::same(\array_keys($calls[0][1]), ['role', 'table', 'database']);
        Assert::same($calls[0][1]['role'], 'user');
        Assert::same($calls[0][1]['table'], 'users');
    }

    public function schemaScopeOverridesDefault(): void
    {
        $factory = (new Factory($this->dbal()))->withDefaultSchemaClasses([SchemaInterface::SCOPE => new SortByIDScope()]);
        $own = new NotDeletedScope();
        $schema = new Schema([
            'user' => [SchemaInterface::TABLE => 'users', SchemaInterface::SCOPE => $own],
            'post' => [SchemaInterface::TABLE => 'posts'],
        ]);

        Assert::same($factory->source($schema, 'user')->getScope(), $own);
        Assert::instanceOf($factory->source($schema, 'post')->getScope(), SortByIDScope::class);
    }

    public function withDefaultSchemaClassesKeepsOriginalFactory(): void
    {
        $factory = new Factory($this->dbal());
        $schema = new Schema(['user' => [SchemaInterface::TABLE => 'users']]);

        $factory->withDefaultSchemaClasses([SchemaInterface::SCOPE => new SortByIDScope()]);

        Assert::null($factory->source($schema, 'user')->getScope());
    }

    public function withCollectionFactoryKeepsOriginalFactory(): void
    {
        $factory = new Factory($this->dbal());
        $collection = new ArrayCollectionFactory();

        $new = $factory->withCollectionFactory(ArrayCollectionFactory::class, $collection);

        Assert::same($new->collection(ArrayCollectionFactory::class), $collection);
        Assert::notSame($factory->collection(ArrayCollectionFactory::class), $collection);
    }

    public function explicitInterfaceOverridesCollectionFactoryInterface(): void
    {
        $factory = (new Factory($this->dbal()))
            ->withCollectionFactory('illuminate', new IlluminateCollectionFactory(), \ArrayAccess::class);

        $collection = $factory->collection(\ArrayObject::class);

        Assert::instanceOf($collection, IlluminateCollectionFactory::class);
    }

    private function hasOne(): array
    {
        return [
            Relation::TYPE => Relation::HAS_ONE,
            Relation::TARGET => 'profile',
            Relation::SCHEMA => [Relation::INNER_KEY => 'id', Relation::OUTER_KEY => 'user_id'],
        ];
    }

    private function typecast(): TypecastInterface
    {
        return new class implements TypecastInterface {
            public function setRules(array $rules): array
            {
                return $rules;
            }
        };
    }

    /**
     * Container stub that records every `make()` call as `[alias, parameters]` and returns `$result`.
     */
    private function recording(array &$calls, object $result): SimpleFactory
    {
        return new SimpleFactory([], static function (string $alias, array $parameters) use (&$calls, $result): object {
            $calls[] = [$alias, $parameters];
            return $result;
        });
    }

    private function dbal(): DatabaseProviderInterface
    {
        $dbal = m::mock(DatabaseProviderInterface::class);
        $dbal->allows('database')->andReturn(m::mock(DatabaseInterface::class));
        return $dbal;
    }
}
