<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\STI;

use Cycle\ORM\Collection\ArrayCollectionFactory;
use Cycle\ORM\Collection\DoctrineCollectionFactory;
use Cycle\ORM\Config\RelationConfig;
use Cycle\ORM\Factory;
use Cycle\ORM\Schema;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Traits\TableTrait;
use Doctrine\Common\Collections\Collection;

abstract class StiBaseTest extends BaseTest
{
    use TableTrait;

    public function setUp(): void
    {
        parent::setUp();

        $factory = (new Factory(
            $this->dbal,
            RelationConfig::getDefault(),
            null,
            new ArrayCollectionFactory()
        ))->withCollectionFactory('doctrine', new DoctrineCollectionFactory(), Collection::class);

        $this->orm = $this->withSchema(new Schema($this->getSchemaArray()))->withFactory($factory);
    }

    abstract protected function getSchemaArray(): array;
}
