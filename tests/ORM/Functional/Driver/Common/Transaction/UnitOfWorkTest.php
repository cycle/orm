<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Transaction;

use Cycle\ORM\Exception\RunnerException;
use Cycle\ORM\Exception\SuccessTransactionRetryException;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\State;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\Post;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Traits\TableTrait;
use Cycle\ORM\Transaction\Runner;
use Cycle\ORM\Transaction\UnitOfWork;

abstract class UnitOfWorkTest extends BaseTest
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

    public function testSuccessRetry(): void
    {
        $eow = new UnitOfWork($this->orm, Runner::outerTransaction());

        $entity = new Post();
        $entity->title = 'Test title';
        $entity->content = 'Test';

        $eow->persistState($entity);

        $result = $eow->run();
        $this->assertFalse($result->isSuccess());
        $this->assertInstanceOf(RunnerException::class, $result->getLastError());

        $this->getDriver()->beginTransaction();

        $result = $result->retry();

        $this->assertTrue($result->isSuccess());
        $this->assertNull($result->getLastError());
    }

    public function testParallelPersist(): void
    {
        $eow = new UnitOfWork($this->orm);
        $eow2 = new UnitOfWork($this->orm);

        $post1 = new Post();
        $post1->title = 'Title1';
        $post1->content = 'Content1';

        $post2 = new Post();
        $post2->title = 'Title2';
        $post2->content = 'Content2';

        $post3 = new Post();
        $post3->title = 'Title3';
        $post3->content = 'Content3';

        $post4 = new Post();
        $post4->title = 'Title4';
        $post4->content = 'Content4';

        $eow->persistState($post1);
        $eow->persistDeferred($post2);
        $eow2->persistState($post3);
        $eow2->persistDeferred($post4);

        $result = $eow->run();
        $result2 = $eow2->run();

        $this->assertTrue($result->isSuccess());
        $this->assertNull($result->getLastError());
        $this->assertTrue($result2->isSuccess());
        $this->assertNull($result2->getLastError());

        $this->orm->getHeap()->clean();
        $stored = (new Select($this->orm, Post::class))
            ->wherePK($post1->id, $post2->id, $post3->id, $post4->id)
            ->fetchAll();

        $this->assertSame('Title1', $stored[0]->title);
        $this->assertSame('Title2', $stored[1]->title);
        $this->assertSame('Title3', $stored[2]->title);
        $this->assertSame('Title4', $stored[3]->title);
    }

    public function testParallelPersistOneFail(): void
    {
        $eow = new UnitOfWork($this->orm, Runner::outerTransaction());
        $eow2 = new UnitOfWork($this->orm);

        $post1 = new Post();
        $post1->title = 'Title1';
        $post1->content = 'Content1';

        $post2 = new Post();
        $post2->title = 'Title2';
        $post2->content = 'Content2';

        $post3 = new Post();
        $post3->title = 'Title3';
        $post3->content = 'Content3';

        $post4 = new Post();
        $post4->title = 'Title4';
        $post4->content = 'Content4';

        $eow->persistState($post1);
        $eow->persistDeferred($post2);
        $eow2->persistState($post3);
        $eow2->persistDeferred($post4);

        $result = $eow->run();
        $result2 = $eow2->run();

        // failed transaction
        $this->assertFalse($result->isSuccess());
        $this->assertInstanceOf(RunnerException::class, $result->getLastError());
        $this->assertNull($post1->id);
        $this->assertNull($post2->id);

        // success transaction
        $this->assertTrue($result2->isSuccess());
        $this->assertNull($result2->getLastError());
        $this->assertNotNull($post3->id);
        $this->assertNotNull($post4->id);

        $this->orm->getHeap()->clean();
        $stored = (new Select($this->orm, Post::class))->wherePK($post3->id, $post4->id)->fetchAll();

        $this->assertSame('Title3', $stored[0]->title);
        $this->assertSame('Title4', $stored[1]->title);
    }

    public function testWithoutTransactionNotChanged(): void
    {
        $eow = new UnitOfWork($this->orm, Runner::outerTransaction());

        $post1 = new Post();
        $post1->title = 'Title1';
        $post1->content = 'Test1';

        $post2 = new Post();
        $post2->title = 'Title2';
        $post2->content = 'Test2';

        $eow->persistState($post1);

        $this->orm->getHeap()->attach($post2, new Node(Node::NEW, ['title' => 'Title2', 'content' => 'Test2'], 'post'));

        $this->orm
            ->getHeap()
            ->get($post2)
            ->setState(new State(Node::MANAGED, ['title' => 'Title2', 'content' => 'Test2']));

        $result = $eow->run();

        $this->assertFalse($result->isSuccess());
        $this->assertInstanceOf(RunnerException::class, $result->getLastError());

        // Node in the transaction, the state cleared
        $this->assertNull($this->orm->getHeap()->get($post1)->getState());

        // Node outside the transaction - the state not changed
        $this->assertSame(
            ['title' => 'Title2', 'content' => 'Test2'],
            $this->orm->getHeap()->get($post2)->getState()->getData()
        );
    }

    public function testCallSuccessRetryException(): void
    {
        $eow = new UnitOfWork($this->orm, Runner::outerTransaction());

        $post = new Post();
        $post->title = 'Title1';
        $post->content = 'Test1';

        $eow->persistState($post);

        $result = $eow->run();

        $this->assertFalse($result->isSuccess());
        $this->assertInstanceOf(RunnerException::class, $result->getLastError());

        $this->getDriver()->beginTransaction();

        $result = $result->retry();

        $this->assertTrue($result->isSuccess());
        $this->assertNull($result->getLastError());

        $this->expectException(SuccessTransactionRetryException::class);
        $result->retry();
    }
}
