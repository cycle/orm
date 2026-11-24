<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Service;

use Cycle\ORM\Exception\ORMException;
use Cycle\ORM\FactoryInterface;
use Cycle\ORM\Heap\Heap;
use Cycle\ORM\MapperInterface;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\RepositoryInterface;
use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select\SourceInterface;
use Cycle\ORM\Service\Implementation\IndexProvider;
use Cycle\ORM\Service\Implementation\MapperProvider;
use Cycle\ORM\Service\Implementation\RepositoryProvider;
use Cycle\ORM\Service\Implementation\RoleResolver;
use Cycle\ORM\Service\Implementation\SourceProvider;
use Cycle\ORM\Service\SourceProviderInterface;
use Cycle\ORM\Tests\Fixtures\Admin;
use Cycle\ORM\Tests\Fixtures\User;
use Mockery as m;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Expect;
use Testo\Test;

#[Test]
#[Covers(IndexProvider::class)]
#[Covers(MapperProvider::class)]
#[Covers(RepositoryProvider::class)]
#[Covers(RoleResolver::class)]
#[Covers(SourceProvider::class)]
final class ProvidersTest
{
    public function indexesListPrimaryKeyOnce(): void
    {
        $provider = new IndexProvider(new Schema([
            'user' => [SchemaInterface::PRIMARY_KEY => 'id', SchemaInterface::FIND_BY_KEYS => ['id', 'email']],
        ]));

        Assert::same(\array_values($provider->getIndexes('user')), ['id', 'email']);
    }

    public function mapperIsMadeOnce(): void
    {
        $factory = m::mock(FactoryInterface::class);
        $factory->allows('mapper')->andReturnUsing(static fn(): MapperInterface => m::mock(MapperInterface::class));
        $provider = new MapperProvider($this->orm(), $factory);

        Assert::same($provider->getMapper('user'), $provider->getMapper('user'));
    }

    public function preparedMappersStayAvailable(): void
    {
        $factory = m::mock(FactoryInterface::class);
        $factory->allows('mapper')->andReturnUsing(static fn(): MapperInterface => m::mock(MapperInterface::class));
        $provider = new MapperProvider($this->orm(), $factory);

        $provider->prepareMappers();

        Assert::instanceOf($provider->getMapper('user'), MapperInterface::class);
        Assert::instanceOf($provider->getMapper('post'), MapperInterface::class);
    }

    public function preparedRepositoriesStayAvailable(): void
    {
        $factory = m::mock(FactoryInterface::class);
        $factory->allows('repository')
            ->andReturnUsing(static fn(): RepositoryInterface => m::mock(RepositoryInterface::class));
        $orm = $this->orm();
        $provider = new RepositoryProvider($orm, m::mock(SourceProviderInterface::class), $orm->getSchema(), $factory);

        $provider->prepareRepositories();

        Assert::instanceOf($provider->getRepository('user'), RepositoryInterface::class);
        Assert::instanceOf($provider->getRepository('post'), RepositoryInterface::class);
    }

    public function mapperIsNotMadeWithoutOrm(): never
    {
        $provider = new MapperProvider(null, m::mock(FactoryInterface::class));

        Expect::exception(ORMException::class)->withMessage('Mapper is not prepared.');

        $provider->getMapper('user');
    }

    public function repositoryIsNotMadeWithoutOrm(): never
    {
        $provider = new RepositoryProvider(
            null,
            m::mock(SourceProviderInterface::class),
            new Schema(['user' => []]),
            m::mock(FactoryInterface::class),
        );

        Expect::exception(ORMException::class)->withMessage('Repository is not prepared.');

        $provider->getRepository('user');
    }

    public function sourceIsMadeOnce(): void
    {
        $factory = m::mock(FactoryInterface::class);
        $factory->allows('source')->andReturnUsing(static fn(): SourceInterface => m::mock(SourceInterface::class));
        $provider = new SourceProvider($factory, new Schema(['user' => []]));

        Assert::same($provider->getSource('user'), $provider->getSource('user'));
    }

    public function subclassOfDefinedClassIsNotResolvedWithoutProxy(): never
    {
        $resolver = new RoleResolver(new Schema([User::class => [SchemaInterface::ROLE => 'user']]), new Heap());

        Expect::exception(ORMException::class)->withMessage(\sprintf('Unable to resolve role of `%s`.', Admin::class));

        $resolver->resolveRole(new Admin());
    }

    public function unresolvedAliasIsRejected(): never
    {
        $schema = m::mock(SchemaInterface::class);
        $schema->allows('resolveAlias')->andReturn(null);
        $resolver = new RoleResolver($schema, new Heap());

        Expect::exception(ORMException::class)->withMessage('Unable to resolve role `user`.');

        $resolver->resolveRole('user');
    }

    private function orm(): ORMInterface
    {
        $orm = m::mock(ORMInterface::class);
        $orm->allows('getSchema')->andReturn(new Schema(['user' => [], 'post' => []]));
        return $orm;
    }
}
