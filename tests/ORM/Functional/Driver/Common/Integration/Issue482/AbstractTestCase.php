<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue482;

use Cycle\ORM\Select;
use Cycle\ORM\Select\QueryBuilder;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\IntegrationTestTrait;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue482\Entity\Country;
use Cycle\ORM\Tests\Traits\TableTrait;

abstract class AbstractTestCase extends BaseTest
{
    use IntegrationTestTrait;
    use TableTrait;

    public function setUp(): void
    {
        // Init DB
        parent::setUp();
        $this->makeTables();
        $this->fillData();

        $this->loadSchema(__DIR__ . '/schema.php');
    }

    public function testSelect(): void
    {
        $select = $this->orm->getRepository(Country::class)
            ->select()
            ->where('is_friendly', true)
            // User wants to search everywhere
            ->with('translations', [
                'as' => 'trans',
                'method' => 4, //JoinableLoader::LEFT_JOIN
                'alias' => 'trans1',
            ])
            // User wants to search everywhere
            ->where(function (QueryBuilder $qb): void {
                $searchProperties = ['code', 'name', 'trans.title'];
                foreach ($searchProperties as $propertyName) {
                    $qb->orWhere($propertyName, 'LIKE', "%eng%");
                }
            })
            // User want to sort by translation
            ->with('translations', [
                'as' => 'transEn',
                'method' => 4, //JoinableLoader::LEFT_JOIN
                'where' => [
                    'locale_id' => 1,
                ],
                'alias' => 'trans2',
            ])
            ->orderBy('transEn.title', 'asc')
            ->load('translations', [
                'using' => 'trans',
            ]);

        $this->assertExpectedSql($select);

        $data = $select->fetchData();
        $this->assertCount(3, $data);
        $this->assertEquals(
            [
                'America on english',
                'China on english',
                'Russia on english',
            ],
            \array_column(
                \array_merge(
                    ...\array_column($data, 'translations')
                ),
                'title'
            )
        );

        $all = $select->fetchAll();
        $this->assertCount(3, $all);
        $this->assertEquals(
            [
                'America on english',
                'China on english',
                'Russia on english',
            ],
            \array_map(
                static function (Country $c) {
                    self::assertCount(1, $c->translations);
                    return $c->translations[0]->title;
                },
                $all
            )
        );
    }

    private function makeTables(): void
    {
        // Make tables
        $this->makeTable('country', [
            'id' => 'primary', // autoincrement
            'name' => 'string',
            'code' => 'string',
            'is_friendly' => 'bool',
        ]);

        $this->makeTable('locale', [
            'id' => 'primary',
            'code' => 'string',
        ]);

        $this->makeTable('translation', [
            'id' => 'primary',
            'title' => 'string',
            'country_id' => 'int',
            'locale_id' => 'int',
        ]);
        $this->makeFK('translation', 'country_id', 'country', 'id', 'NO ACTION', 'NO ACTION');
        $this->makeFK('translation', 'locale_id', 'locale', 'id', 'NO ACTION', 'NO ACTION');
    }

    private function fillData(): void
    {
        $this->getDatabase()->table('translation')->delete()->run();
        $this->getDatabase()->table('country')->delete()->run();
        $this->getDatabase()->table('locale')->delete()->run();

        $en = 1;
        $this->getDatabase()->table('locale')->insertMultiple(
            ['code'],
            [
                ['en'],
            ],
        );
        $this->getDatabase()->table('country')->insertMultiple(
            ['name', 'code', 'is_friendly'],
            [
                ['Russia', 'RUS', true],
                ['USA', 'USA', true],
                ['China', 'CHN', true],
            ],
        );

        $this->getDatabase()->table('translation')->insertMultiple(
            ['country_id', 'locale_id', 'title'],
            [
                [1, $en, 'Russia on english'],
                [2, $en, 'America on english'],
                [3, $en, 'China on english'],
            ],
        );
    }

    abstract protected function assertExpectedSql(Select $select): void;
}
