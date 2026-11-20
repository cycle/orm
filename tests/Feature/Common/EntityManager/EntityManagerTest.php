<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\EntityManager;

use Cycle\Database\Exception\StatementException\ConstrainException;
use Cycle\ORM\EntityManager;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\Post;
use Cycle\ORM\Transaction\Runner;
use Cycle\Testing\ORM\OrmTestCase;
use Testo\Assert;
use Testo\Expect;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

final class EntityManagerTest extends OrmTestCase
{
    #[Test]
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
        Assert::true($result->isSuccess());
        $this->assertNumWrites(0);

        Assert::true($this->orm->getHeap()->has($entity0));
        Assert::true($this->orm->getHeap()->has($entity1));
    }

    #[Test]
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
        Assert::true($result->isSuccess());
        $this->assertNumWrites(0);

        Assert::false($this->orm->getHeap()->has($entity0));
        Assert::false($this->orm->getHeap()->has($entity1));
    }

    /**
     * Deferred persisting should be calc changes after the `run()` method calling
     */
    #[Test]
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
        Assert::true($result->isSuccess());
        $this->assertNumWrites(1);

        $this->orm->getHeap()->clean();
        $stored = (new Select($this->orm, Post::class))->wherePK($entity->id)->fetchOne();

        Assert::same($stored->title, 'changed title');
    }

    /**
     * Not deferred persisting should calc changes on entity when adding into transaction
     */
    #[Test]
    public function testPersistState(): void
    {
        $em = new EntityManager($this->orm);

        $entity = new Post();
        $entity->title = 'Test title';
        $entity->content = 'Test content';

        $em->persistState($entity);
        $entity->title = 'changed title';

        $this->captureWriteQueries();
        $result = $em->run();
        Assert::true($result->isSuccess());
        $this->assertNumWrites(1);

        Assert::notNull($entity->id);

        $this->orm->getHeap()->clean();
        $stored = (new Select($this->orm, Post::class))->wherePK($entity->id)->fetchOne();

        Assert::same($stored->title, 'Test title');
        Assert::same($entity->title, 'changed title');
    }

    #[Test]
    public function testPersistStateTwiceAndChangeEntity(): void
    {
        $em = new EntityManager($this->orm);

        $entity = new Post();
        $entity->title = 'Test 1';
        $entity->content = 'Test content';

        $em->persistState($entity);
        $entity->title = 'Title 2';
        $em->persistState($entity);
        $entity->title = 'Title 3';

        $this->captureWriteQueries();
        $result = $em->run();
        Assert::true($result->isSuccess());
        $this->assertNumWrites(1);

        Assert::notNull($entity->id);

        $this->orm->getHeap()->clean();
        $stored = (new Select($this->orm, Post::class))->wherePK($entity->id)->fetchOne();

        Assert::same($stored->title, 'Title 2');
    }

    #[Test]
    public function testPersistStateTwiceAndChangeEntityWithStoredEntity(): void
    {
        $em = new EntityManager($this->orm);

        $entity = new Post();
        $entity->title = 'Test 1';
        $entity->content = 'Test content';
        $this->save($entity);

        $entity->title = 'Title 2';
        $em->persistState($entity);
        $entity->title = 'Title 3';
        $em->persistState($entity);
        $entity->title = 'Title 4';

        $this->captureWriteQueries();
        $result = $em->run();
        Assert::true($result->isSuccess());
        $this->assertNumWrites(1);

        Assert::notNull($entity->id);

        $this->orm->getHeap()->clean();
        $stored = (new Select($this->orm, Post::class))->wherePK($entity->id)->fetchOne();

        Assert::same($stored->title, 'Title 3');
    }

    /**
     * @link https://github.com/cycle/orm/issues/355
     */
    #[Test]
    public function testPersistStateTwice(): void
    {
        $em = new EntityManager($this->orm);

        $entity = $this->orm->make(Post::class, [
            'title' => 'Test title',
            'content' => 'Test content',
        ]);

        $this->captureWriteQueries();
        $em->persistState($entity)->run();
        $this->assertNumWrites(1);

        Assert::notNull($entity->id);

        $this->captureWriteQueries();
        $em->persistState($entity)->run();
        $this->assertNumWrites(0);
    }

    #[Test]
    public function testRunException(): void
    {
        $em = new EntityManager($this->orm);

        $entity = new Post();
        $entity->title = 'Test title';

        $em->persistState($entity);

        Expect::exception(ConstrainException::class);
        $em->run();
    }

    #[Test]
    public function testRunError(): void
    {
        $em = new EntityManager($this->orm);

        $entity = new Post();
        $entity->title = 'Test title';

        $em->persistState($entity);

        $result = $em->run(false);

        Assert::false($result->isSuccess());
        Assert::instanceOf($result->getLastError(), ConstrainException::class);
    }

    #[Test]
    public function givenRunnerRunsTheCommands(): void
    {
        $runner = Runner::innerTransaction();
        $entity = new Post();
        $entity->title = 'Test title';
        $entity->content = 'Test content';

        (new EntityManager($this->orm))->persist($entity)->run(runner: $runner);

        Assert::same($runner->count(), 1);
        Assert::same($this->getDatabase()->table('post')->count(), 3);
    }

    #[Test]
    public function entityIsDeletedWithoutCascade(): void
    {
        $entity = (new Select($this->orm, Post::class))->wherePK(1)->fetchOne();

        $state = (new EntityManager($this->orm))->delete($entity, false)->run();

        Assert::true($state->isSuccess());
        Assert::same($this->getDatabase()->table('post')->select('title')->fetchAll(), [['title' => 'bar']]);
        Assert::false($this->orm->getHeap()->has($entity));
    }

    #[BeforeTest]
    public function setUp(): void
    {
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
            ],
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

    // todo test parallel transactions running
}
