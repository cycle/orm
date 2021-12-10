<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\EntityManager;

use Cycle\Database\Exception\StatementException\ConstrainException;
use Cycle\ORM\EntityManager;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Fixtures\Post;
use Cycle\ORM\Tests\Traits\TableTrait;

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
        $em->persistState($entity1);
        $em->persist($entity2);

        $em->clean();

        $this->captureWriteQueries();
        $result = $em->run();
        $this->assertTrue($result->isSuccess());
        $this->assertNumWrites(0);
        $this->assertTrue($this->orm->getHeap()->has($entity0));
        $this->assertTrue($this->orm->getHeap()->has($entity1));
    }

    public function testCleanWithHeap(): void
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
        $em->persistState($entity1);
        $em->persist($entity2);

        $em->clean(true);

        $this->captureWriteQueries();
        $result = $em->run();
        $this->assertTrue($result->isSuccess());
        $this->assertNumWrites(0);

        $this->assertFalse($this->orm->getHeap()->has($entity0));
        $this->assertFalse($this->orm->getHeap()->has($entity1));
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

        $em->persist($entity);
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

        $em->persistState($entity);
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

    public function testRunException(): void
    {
        $em = new EntityManager($this->orm);

        $entity = new Post();
        $entity->title = 'Test title';

        $em->persistState($entity);

        $this->expectException(ConstrainException::class);
        $em->run();
    }

    public function testRunError(): void
    {
        $em = new EntityManager($this->orm);

        $entity = new Post();
        $entity->title = 'Test title';

        $em->persistState($entity);

        $result = $em->run(false);

        $this->assertFalse($result->isSuccess());
        $this->assertInstanceOf(ConstrainException::class, $result->getLastError());
    }

    // todo test parallel transactions running
}
