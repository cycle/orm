<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Parser;

use Cycle\ORM\Parser\CastableInterface;
use Cycle\ORM\Parser\CompositeTypecast;
use Cycle\ORM\Parser\TypecastInterface;
use Cycle\ORM\Parser\UncastableInterface;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class CompositeTypecastTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testSetRules()
    {
        $rules = $initialRules = [
            'foo' => 'bar',
            'baz' => 'bar',
            'bar' => 'bar',
        ];

        $casters = [
            [
                'typecast' => m::mock(TypecastInterface::class),
                'expects' => $rules,
                'returns' => [
                    'foo1' => 'bar',
                    'baz' => 'bar',
                    'bar' => 'bar',
                ],
            ],
            [
                'typecast' => m::mock(CastableInterface::class),
                'returns' => [
                    'foo1' => 'bar',
                    'baz' => 'bar',
                    'bar' => 'bar',
                ],
            ],
            [
                'typecast' => m::mock(UncastableInterface::class),
                'returns' => [
                    'foo' => 'bar',
                    'bar' => 'bar',
                ],
            ],
            [
                'typecast' => m::mock(CastableInterface::class, UncastableInterface::class),
                'returns' => [
                    'baz' => 'bar',
                ],
            ],
        ];

        $castData = [
            'foo' => 'bar',
        ];

        $uncastData = [
            'baz' => 'bar',
        ];

        foreach ($casters as $data) {
            $typecast = $data['typecast'];

            $typecast->shouldReceive('setRules')->once()
                ->with($rules)
                ->andReturn($data['returns']);

            if ($typecast instanceof CastableInterface) {
                $typecast->shouldReceive('cast')->once()->with($castData)->andReturn($castData);
            }

            if ($typecast instanceof UncastableInterface) {
                $typecast->shouldReceive('uncast')->once()->with($uncastData)->andReturn($uncastData);
            }

            $rules = $data['returns'];
        }

        $typecast = new CompositeTypecast(
            ... array_map(static fn(array $data) => $data['typecast'], $casters)
        );

        $this->assertSame([
            'baz' => 'bar',
        ], $typecast->setRules($initialRules));

        $this->assertSame($castData, $typecast->cast($castData));
        $this->assertSame($uncastData, $typecast->uncast($uncastData));
    }

    public function tearDown(): void
    {
        m::close();
    }
}
