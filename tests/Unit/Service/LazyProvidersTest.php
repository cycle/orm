<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Service;

use Cycle\Database\DatabaseInterface;
use Cycle\ORM\Exception\ORMException;
use Cycle\ORM\FactoryInterface;
use Cycle\ORM\Parser\TypecastInterface;
use Cycle\ORM\Schema;
use Cycle\ORM\Select\SourceInterface;
use Cycle\ORM\Service\Implementation\RelationProvider;
use Cycle\ORM\Service\Implementation\TypecastProvider;
use Cycle\ORM\Service\SourceProviderInterface;
use Mockery as m;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

#[Test]
final class LazyProvidersTest
{
    public function relationMapIsNotBuiltWithoutOrm(): never
    {
        $provider = new RelationProvider(null);

        Expect::exception(ORMException::class)->withMessage('Relation Map is not prepared.');

        $provider->getRelationMap('user');
    }

    public function typecastIsMadeOnce(): void
    {
        $schema = new Schema(['user' => []]);
        $database = m::mock(DatabaseInterface::class);
        $source = m::mock(SourceInterface::class);
        $source->allows('getDatabase')->andReturn($database);
        $sources = m::mock(SourceProviderInterface::class);
        $sources->allows('getSource')->with('user')->andReturn($source);
        $typecast = m::mock(TypecastInterface::class);
        $factory = m::mock(FactoryInterface::class);
        $factory->shouldReceive('typecast')->once()->with($schema, $database, 'user')->andReturn($typecast);
        $provider = new TypecastProvider($factory, $schema, $sources);

        Assert::same($provider->getTypecast('user'), $typecast);
        Assert::same($provider->getTypecast('user'), $typecast);
    }

    public function missingTypecastIsRemembered(): void
    {
        $source = m::mock(SourceInterface::class);
        $source->allows('getDatabase')->andReturn(m::mock(DatabaseInterface::class));
        $sources = m::mock(SourceProviderInterface::class);
        $sources->allows('getSource')->andReturn($source);
        $factory = m::mock(FactoryInterface::class);
        $factory->shouldReceive('typecast')->once()->andReturn(null);
        $provider = new TypecastProvider($factory, new Schema(['user' => []]), $sources);

        Assert::null($provider->getTypecast('user'));
        Assert::null($provider->getTypecast('user'));
    }
}
