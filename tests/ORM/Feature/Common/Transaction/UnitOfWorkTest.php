<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Transaction;

use Cycle\ORM\Exception\RunnerException;
use Cycle\ORM\Exception\SuccessTransactionRetryException;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\State;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\Post;
use Cycle\ORM\Transaction\Runner;
use Cycle\ORM\Transaction\UnitOfWork;
use Cycle\Testing\ORM\OrmTestCase;

class UnitOfWorkTest extends OrmTestCase
{

    #[\Testo\Test]
    public function testSuccessRetry(): void
    {
        $eow = new UnitOfWork($this->orm, Runner::outerTransaction());

        $entity = new Post();
        $entity->title = 'Test title';
        $entity->content = 'Test';

        $eow->persistState($entity);

        $result = $eow->run();
        \Testo\Assert::false($result->isSuccess());
        \Testo\Assert::instanceOf($result->getLastError(), RunnerException::class);

        $this->database->getDriver()->beginTransaction();

        $result = $result->retry();

        \Testo\Assert::true($result->isSuccess());
        \Testo\Assert::null($result->getLastError());
    }

    #[\Testo\Test]
    public function testPendingChanges(): void
    {
        $eow = new UnitOfWork($this->orm, Runner::outerTransaction());

        $entity = new Post();
        $entity->title = 'Test title';
        $entity->content = 'Test';

        $eow->persistState($entity);

        \Testo\Assert::true($eow->hasPendingChanges());

        $result = $eow->run();
        \Testo\Assert::true($eow->hasPendingChanges());
        \Testo\Assert::false($result->isSuccess());
        \Testo\Assert::instanceOf($result->getLastError(), RunnerException::class);

        $this->database->getDriver()->beginTransaction();

        $result = $result->retry();

        \Testo\Assert::false($eow->hasPendingChanges());
        \Testo\Assert::true($result->isSuccess());
        \Testo\Assert::null($result->getLastError());
    }

    #[\Testo\Test]
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

        \Testo\Assert::true($result->isSuccess());
        \Testo\Assert::null($result->getLastError());
        \Testo\Assert::true($result2->isSuccess());
        \Testo\Assert::null($result2->getLastError());

        $this->orm->getHeap()->clean();
        $stored = (new Select($this->orm, Post::class))
            ->wherePK($post1->id, $post2->id, $post3->id, $post4->id)
            ->fetchAll();

        \Testo\Assert::same($stored[0]->title, 'Title1');
        \Testo\Assert::same($stored[1]->title, 'Title2');
        \Testo\Assert::same($stored[2]->title, 'Title3');
        \Testo\Assert::same($stored[3]->title, 'Title4');
    }

    #[\Testo\Test]
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
        \Testo\Assert::false($result->isSuccess());
        \Testo\Assert::instanceOf($result->getLastError(), RunnerException::class);
        \Testo\Assert::null($post1->id);
        \Testo\Assert::null($post2->id);

        // success transaction
        \Testo\Assert::true($result2->isSuccess());
        \Testo\Assert::null($result2->getLastError());
        \Testo\Assert::notNull($post3->id);
        \Testo\Assert::notNull($post4->id);

        $this->orm->getHeap()->clean();
        $stored = (new Select($this->orm, Post::class))->wherePK($post3->id, $post4->id)->fetchAll();

        \Testo\Assert::same($stored[0]->title, 'Title3');
        \Testo\Assert::same($stored[1]->title, 'Title4');
    }

    #[\Testo\Test]
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

        \Testo\Assert::false($result->isSuccess());
        \Testo\Assert::instanceOf($result->getLastError(), RunnerException::class);

        // Node in the transaction, the state cleared
        \Testo\Assert::null($this->orm->getHeap()->get($post1)->getState());

        // Node outside the transaction - the state not changed
        \Testo\Assert::same($this->orm->getHeap()->get($post2)->getState()->getData(), ['title' => 'Title2', 'content' => 'Test2']);
    }

    #[\Testo\Test]
    public function testCallSuccessRetryException(): void
    {
        $eow = new UnitOfWork($this->orm, Runner::outerTransaction());

        $post = new Post();
        $post->title = 'Title1';
        $post->content = 'Test1';

        $eow->persistState($post);

        $result = $eow->run();

        \Testo\Assert::false($result->isSuccess());
        \Testo\Assert::instanceOf($result->getLastError(), RunnerException::class);

        $this->database->getDriver()->beginTransaction();

        $result = $result->retry();

        \Testo\Assert::true($result->isSuccess());
        \Testo\Assert::null($result->getLastError());

        \Testo\Expect::exception(SuccessTransactionRetryException::class);
        $result->retry();
    }

    #[\Testo\Lifecycle\BeforeTest]
    public function setUp(): void
    {
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
}
