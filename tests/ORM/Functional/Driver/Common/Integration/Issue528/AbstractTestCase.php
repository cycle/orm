<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue528;

use Cycle\ORM\Select;
use Cycle\ORM\Select\JoinableLoader;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\IntegrationTestTrait;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue528\Entity\Country;
use Cycle\ORM\Tests\Traits\TableTrait;
use Spiral\Pagination\Paginator;

abstract class AbstractTestCase extends BaseTest
{
    use IntegrationTestTrait;
    use TableTrait;

    public function testLoadWherePagination(): void
    {
        $select = (new Select($this->orm, Country::class))
            ->load('translations')
            ->where('translations.locale_id', 1);
        /** @var Paginator $paginator */
        $paginator = (new Paginator(2))->paginate($select);
        $data = $select->fetchData();
        $this->assertSame(10, $paginator->count());
        $this->assertCount(2, $data);
    }

    public function testWithLeft(): void
    {
        $select = (new Select($this->orm, Country::class))
            ->with('translations', [
                'as' => 'trans',
                'alias' => 'trans',
                'method' => JoinableLoader::LEFT_JOIN,
            ]);
        /** @var Paginator $paginator */
        $paginator = (new Paginator(2))->paginate($select);
        $data = $select->fetchData();
        $this->assertSame(10, $paginator->count());
        $this->assertCount(2, $data);
    }

    public function testWithInner(): void
    {
        $select = (new Select($this->orm, Country::class))
            ->with('translations', [
                'as' => 'trans',
                'alias' => 'trans',
                'method' => JoinableLoader::JOIN,
            ]);
        /** @var Paginator $paginator */
        $paginator = (new Paginator(2))->paginate($select);
        $data = $select->fetchData();
        $this->assertSame(10, $paginator->count());
        $this->assertCount(2, $data);
    }

    public function setUp(): void
    {
        // Init DB
        parent::setUp();
        $this->makeTables();
        $this->fillData();

        $this->loadSchema(__DIR__ . '/schema.php');
    }

    private function makeTables(): void
    {
        // Make tables
        $this->makeTable('country', [
            'id' => 'primary', // autoincrement
            'name' => 'string',
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

        $locales = [
            ['en'],
            ['ru'],
        ];
        $this->getDatabase()->table('locale')->insertMultiple(
            ['code'],
            $locales,
        );
        $countries = [
            ['Russia'],
            ['USA'],
            ['China'],
            ['Kazakhstan'],
            ['Japan'],
            ['Germany'],
            ['France'],
            ['Italy'],
            ['Spain'],
            ['India'],
        ];
        $this->getDatabase()->table('country')->insertMultiple(
            ['name'],
            $countries,
        );

        $values = [];
        foreach ($countries as $i => $country) {
            // Duplicate translations for each locale.
            // There are 2 same translations for each country.
            for ($k = 0; $k < 2; $k++) {
                foreach ($locales as $j => $locale) {
                    $values[] = [$i + 1, $j + 1, "{$country[0]}-{$locale[0]}"];
                }
            }
        }
        $this->getDatabase()->table('translation')->insertMultiple(
            ['country_id', 'locale_id', 'title'],
            $values,
        );
    }
}
