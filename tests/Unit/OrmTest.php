<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit;

use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\DatabaseManager;
use Cycle\ORM\Config\RelationConfig;
use Cycle\ORM\Factory;
use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\ORM;
use Cycle\ORM\Schema;
use Cycle\ORM\Service\MapperProviderInterface;
use Cycle\ORM\Tests\Util\SimpleFactory;
use Cycle\ORM\Transaction\CommandGenerator;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

#[Test]
final class OrmTest
{
    public function testPrepareServicesTwice(): void
    {
        $orm = $this->createOrm();
        $orm->prepareServices();
        $orm->prepareServices();
        Assert::true(true, 'There aren\'t any errors.');
    }

    public function testORMClone(): void
    {
        $orm = $this->createOrm();
        $new = $orm->withFactory($orm->getFactory());
        Assert::notSame($new, $orm);
    }

    public function testORMCloneWithSchema(): void
    {
        $orm = $this->createOrm();
        $new = $orm->with(new Schema([]));

        Assert::notSame($orm, $new);
        Assert::notSame($orm->getSchema(), $new->getSchema());
    }

    public function testORMCloneWithFactory(): void
    {
        $orm = $this->createOrm();
        $new = $orm->with(factory: new Factory(new DatabaseManager(new DatabaseConfig([]))));

        Assert::notSame($new, $orm);
        Assert::notSame($orm->getFactory(), $new->getFactory());
    }

    public function testORMCloneWithHeap(): void
    {
        $orm = $this->createOrm();
        $new = $orm->with(heap: new Heap());

        Assert::notSame($orm, $new);
        Assert::notSame($orm->getHeap(), $new->getHeap());
    }

    public function givenHeapAndCommandGeneratorAreUsed(): void
    {
        $heap = new Heap();
        $generator = new CommandGenerator();

        $orm = $this->createOrm(heap: $heap, commandGenerator: $generator);

        Assert::same($orm->getHeap(), $heap);
        Assert::same($orm->getCommandGenerator(), $generator);
    }

    public function withGivenHeapUsesItCleaned(): void
    {
        $orm = $this->createOrm();
        $heap = $this->heapWith($entity = new \stdClass());

        $new = $orm->with(heap: $heap);

        Assert::same($new->getHeap(), $heap);
        Assert::false($heap->has($entity));
    }

    public function withoutHeapUsesCleanCopy(): void
    {
        $heap = $this->heapWith($entity = new \stdClass());
        $orm = $this->createOrm(heap: $heap);

        $new = $orm->with();

        Assert::notSame($new->getHeap(), $heap);
        Assert::false($new->getHeap()->has($entity));
        Assert::true($heap->has($entity));
    }

    public function cloneGetsCleanHeapAndOwnServices(): void
    {
        $heap = $this->heapWith($entity = new \stdClass());
        $orm = $this->createOrm(heap: $heap);

        $clone = clone $orm;

        Assert::notSame($clone->getHeap(), $heap);
        Assert::false($clone->getHeap()->has($entity));
        Assert::true($heap->has($entity));
        Assert::notSame($clone->getService(MapperProviderInterface::class), $orm->getService(MapperProviderInterface::class));
    }

    public function unknownServiceIsRejected(): never
    {
        $orm = $this->createOrm();

        Expect::exception(\InvalidArgumentException::class)->withMessage('Undefined service `stdClass`.');

        $orm->getService(\stdClass::class);
    }

    public function indexesComeFromSchema(): void
    {
        $orm = $this->createOrm(schema: new Schema([
            'user' => [
                Schema::ENTITY => \stdClass::class,
                Schema::PRIMARY_KEY => 'id',
                Schema::FIND_BY_KEYS => ['email'],
            ],
        ]));

        Assert::same(\array_values($orm->getIndexes(\stdClass::class)), ['id', 'email']);
    }

    public function debugInfoShowsSchema(): void
    {
        $schema = new Schema([]);

        Assert::same($this->createOrm(schema: $schema)->__debugInfo(), ['schema' => $schema]);
    }

    private function heapWith(object $entity): Heap
    {
        $heap = new Heap();
        $heap->attach($entity, new Node(Node::MANAGED, ['id' => 1], 'user'));
        return $heap;
    }

    private function createOrm(
        ?Heap $heap = null,
        ?CommandGenerator $commandGenerator = null,
        Schema $schema = new Schema([]),
    ): ORM {
        return new ORM(
            factory: new Factory(
                new DatabaseManager(new DatabaseConfig([])),
                RelationConfig::getDefault(),
                new SimpleFactory(),
            ),
            schema: $schema,
            commandGenerator: $commandGenerator,
            heap: $heap,
        );
    }
}
