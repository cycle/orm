<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Parser;

use Cycle\ORM\Parser\CastableInterface;
use Cycle\ORM\Parser\CompositeTypecast;
use Cycle\ORM\Parser\TypecastInterface;
use Cycle\ORM\Parser\UncastableInterface;
use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class CompositeTypecastTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const RULES_INITIAL = [
        'foo' => 'bar',
        'baz' => 'bar',
        'bar' => 'bar',
    ];
    private const RULES_RESULT = [
        'baz' => 'bar',
    ];

    private const CAST_INITIAL = [
        'foo' => 'foo',
        'bar' => 'bar',
    ];
    private const CAST_RESULT = [
        'foo' => 'bar_final',
        'bar' => 'bar_final',
    ];

    private const UNCAST_INITIAL = [
        'foo' => 'unfoo',
        'bar' => 'unbar',
    ];
    private const UNCAST_RESULT = [
        'foo' => 'unbar_final',
        'bar' => 'unbar_final',
    ];

    private const OPT_TYPECAST = 0;
    private const OPT_RULES_RETURNS = 1;
    private const OPT_CAST_RETURNS = 2;
    private const OPT_UNCAST_RETURNS = 3;

    public function typecastProvider(): iterable
    {
        yield [
            [
                [
                    self::OPT_TYPECAST => m::mock(TypecastInterface::class),
                    self::OPT_RULES_RETURNS => [
                        'foo1' => 'bar',
                        'baz' => 'bar',
                        'bar' => 'bar',
                    ],
                ],
                [
                    self::OPT_TYPECAST => m::mock(CastableInterface::class),
                    self::OPT_RULES_RETURNS => [
                        'foo1' => 'bar',
                        'baz' => 'bar',
                        'bar' => 'bar',
                    ],
                    self::OPT_CAST_RETURNS => [
                        'foo' => 'foo1',
                        'bar' => 'bar1',
                    ],
                ],
                [
                    self::OPT_TYPECAST => m::mock(UncastableInterface::class),
                    self::OPT_RULES_RETURNS => [
                        'foo' => 'bar',
                        'bar' => 'bar',
                    ],
                    self::OPT_UNCAST_RETURNS => self::UNCAST_RESULT,
                ],
                [
                    self::OPT_TYPECAST => m::mock(CastableInterface::class, UncastableInterface::class),
                    self::OPT_RULES_RETURNS => self::RULES_RESULT,
                    self::OPT_CAST_RETURNS => self::CAST_RESULT,
                    self::OPT_UNCAST_RETURNS => self::UNCAST_INITIAL,
                ],
            ],
        ];
    }

    /**
     * @dataProvider typecastProvider()
     */
    public function testSetRules(array $casters): void
    {
        $rules = self::RULES_INITIAL;
        foreach ($casters as $data) {
            /** @var m\Mock $typecast */
            $typecast = $data[self::OPT_TYPECAST];

            $typecast->shouldReceive('setRules')->once()
                ->with($rules)
                ->andReturn($data[self::OPT_RULES_RETURNS]);

            $rules = $data[self::OPT_RULES_RETURNS];
        }

        $typecast = new CompositeTypecast(
            ...array_map(static fn (array $data) => $data[self::OPT_TYPECAST], $casters)
        );

        $this->assertSame(self::RULES_RESULT, $typecast->setRules(self::RULES_INITIAL));
    }

    /**
     * @dataProvider typecastProvider()
     */
    public function testCast(array $casters): void
    {
        $values = self::CAST_INITIAL;
        foreach ($casters as $data) {
            /** @var m\Mock $typecast */
            $typecast = $data[self::OPT_TYPECAST];
            if (!$typecast instanceof CastableInterface) {
                continue;
            }

            $typecast->shouldReceive('cast')->once()
                ->with($values)
                ->andReturn($data[self::OPT_CAST_RETURNS]);

            $values = $data[self::OPT_CAST_RETURNS];
        }

        $typecast = new CompositeTypecast(
            ...array_map(static fn (array $data) => $data[self::OPT_TYPECAST], $casters)
        );

        $this->assertSame(self::CAST_RESULT, $typecast->cast(self::CAST_INITIAL));
    }

    /**
     * @dataProvider typecastProvider()
     */
    public function testUncast(array $casters): void
    {
        $values = self::UNCAST_INITIAL;
        $reversed = \array_reverse($casters);
        foreach ($reversed as $data) {
            /** @var m\Mock $typecast */
            $typecast = $data[self::OPT_TYPECAST];
            if (!$typecast instanceof UncastableInterface) {
                continue;
            }

            $typecast->shouldReceive('uncast')->once()
                ->with($values)
                ->andReturn($data[self::OPT_UNCAST_RETURNS]);

            $values = $data[self::OPT_UNCAST_RETURNS];
        }

        $typecast = new CompositeTypecast(
            ...array_map(static fn (array $data) => $data[self::OPT_TYPECAST], $casters)
        );

        $this->assertSame(self::UNCAST_RESULT, $typecast->uncast(self::UNCAST_INITIAL));
    }

    public function tearDown(): void
    {
        m::close();
    }
}
