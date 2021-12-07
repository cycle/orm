<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\EntityManager;

use Cycle\ORM\EntityManager;
use Cycle\ORM\Exception\RunnerException;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Fixtures\Post;
use Cycle\ORM\Tests\Traits\TableTrait;
use Cycle\ORM\Transaction\Runner;

abstract class EntityManagerTest extends BaseTest
{
    use TableTrait;

    public function setUp(): void
    {
        parent::setUp();

        $this->makeTable('post', [
            'id' => 'primary',
            'title' => 'string',
            'content' => 'string',
        ]);

        $this->getDatabase()->table('post')->insertMultiple(
            ['title', 'content'],
            [
                ['foo', 'foofoo'],
                ['bar', 'barbar'],
            ]
        );

        $this->orm = $this->withSchema(new Schema([
            Post::class => [
                SchemaInterface::ROLE => 'post',
                SchemaInterface::MAPPER => Mapper::class,
                SchemaInterface::DATABASE => 'default',
                SchemaInterface::TABLE => 'post',
                SchemaInterface::PRIMARY_KEY => 'id',
                SchemaInterface::COLUMNS => ['id', 'title', 'content'],
                SchemaInterface::SCHEMA => [],
                SchemaInterface::RELATIONS => [],
            ],
        ]));
    }

    public function testClean(): void
    {
        $em = new EntityManager($this->orm);

        $entity0 = (new Select($this->orm, Post::class))->wherePK(1)->fetchOne();
        $entity1 = new Post();
        $entity1->title = 'Test title';
        $entity1->content = 'Test content';
        $entity2 = new Post();
        $entity2->title = 'Test title';
        $entity2->content = 'Test content';

        $em->delete($entity0);
        $em->persist($entity1);
        $em->persistDeferred($entity2);

        $em->clean();

        $this->captureWriteQueries();
        $result = $em->run();
        $this->assertTrue($result->isSuccess());
        $this->assertNumWrites(0);
    }

    /**
     * Deferred persisting should be calc changes after the `run()` method calling
     */
    public function testPersistDeferred(): void
    {
        $em = new EntityManager($this->orm);

        $entity = new Post();
        $entity->title = 'Test title';
        $entity->content = 'Test content';

        $em->persistDeferred($entity);
        $entity->title = 'changed title';

        $this->captureWriteQueries();
        $result = $em->run();

        $this->assertTrue($result->isSuccess());
        $this->assertNumWrites(1);

        $this->orm->getHeap()->clean();
        $stored = (new Select($this->orm, Post::class))->wherePK($entity->id)->fetchOne();

        $this->assertSame('changed title', $stored->title);
    }

    /**
     * Not deferred persisting should be calc changes on entity adding into transaction
     */
    public function testPersist(): void
    {
        $em = new EntityManager($this->orm);

        $entity = new Post();
        $entity->title = 'Test title';
        $entity->content = 'Test content';

        $em->persist($entity);
        $entity->title = 'changed title';

        $this->captureWriteQueries();
        $result = $em->run();

        $this->assertTrue($result->isSuccess());
        $this->assertNumWrites(1);
        $this->assertNotNull($entity->id);

        $this->orm->getHeap()->clean();
        $stored = (new Select($this->orm, Post::class))->wherePK($entity->id)->fetchOne();

        $this->assertSame('Test title', $stored->title);
        $this->assertSame('changed title', $entity->title);
    }

    public function testRunContinueTransaction(): void
    {
        $em = new EntityManager($this->orm);

        $this->getDriver()->beginTransaction();
        $this->assertSame(1, $this->getDriver()->getTransactionLevel());

        $entity = new Post();
        $entity->title = 'Test title';
        $entity->content = 'Test content';

        $em->persist($entity);
        $result = $em->run(Runner::continueTransaction());

        $this->assertTrue($result->isSuccess());

        // No new transaction was opened. A previously opened manually transaction is closed
        $this->assertSame(1, $this->getDriver()->getTransactionLevel());
    }

    public function testRunContinueWrongTransaction(): void
    {
        $em = new EntityManager($this->orm);

        $entity = new Post();
        $entity->title = 'Test title';
        $entity->content = 'Test content';

        $em->persist($entity);
        $result = $em->run(Runner::continueTransaction());

        $this->assertFalse($result->isSuccess());
        $exception = $result->getError();

        $this->assertInstanceOf(RunnerException::class, $exception);
    }

    public function testRunNewTransaction(): void
    {
        $em = new EntityManager($this->orm);

        $this->getDriver()->beginTransaction();
        $this->assertSame(1, $this->getDriver()->getTransactionLevel());

        $entity = new Post();
        $entity->title = 'Test title';
        $entity->content = 'Test content';

        $em->persist($entity);
        $result = $em->run();

        $this->assertTrue($result->isSuccess());

        // Opened and closed new transaction. Transaction level not changed
        $this->assertSame(1, $this->getDriver()->getTransactionLevel());
    }

    // todo test parallel transactions running
}
