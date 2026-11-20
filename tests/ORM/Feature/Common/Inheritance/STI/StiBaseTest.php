<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Inheritance\STI;

use Cycle\ORM\Collection\ArrayCollectionFactory;
use Cycle\ORM\Collection\DoctrineCollectionFactory;
use Cycle\ORM\Config\RelationConfig;
use Cycle\ORM\Factory;
use Cycle\ORM\Schema;
use Cycle\Testing\ORM\OrmTestCase;

abstract class StiBaseTest extends OrmTestCase
{

    #[\Testo\Lifecycle\BeforeTest]
    public function setUp(): void
    {
        $factory = (new Factory(
            $this->dbal,
            RelationConfig::getDefault(),
            null,
            new ArrayCollectionFactory(),
        ))->withCollectionFactory('doctrine', new DoctrineCollectionFactory());

        $this->orm = $this->withSchema(new Schema($this->getSchemaArray()))->withFactory($factory);
    }

    abstract protected function getSchemaArray(): array;
}
