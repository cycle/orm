<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Transaction;

use Cycle\ORM\Command\CommandInterface;
use Cycle\ORM\Exception\RunnerException;
use Cycle\ORM\Exception\SuccessTransactionRetryException;
use Cycle\ORM\Exception\TransactionException;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\State;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\Post;
use Cycle\ORM\Transaction\Runner;
use Cycle\ORM\Transaction\RunnerInterface;
use Cycle\ORM\Transaction\UnitOfWork;
use Cycle\Testing\ORM\OrmTestCase;
use Testo\Assert;
use Testo\Data\DataSet;
use Testo\Expect;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

final class UnitOfWorkTest extends OrmTestCase
{
    #[Test]
    public function testSuccessRetry(): void
    {
        $eow = new UnitOfWork($this->orm, Runner::outerTransaction());

        $entity = new Post();
        $entity->title = 'Test title';
        $entity->content = 'Test';

        $eow->persistState($entity);

        $result = $eow->run();
        Assert::false($result->isSuccess());
        Assert::instanceOf($result->getLastError(), RunnerException::class);

        $this->database->getDriver()->beginTransaction();

        $result = $result->retry();

        Assert::true($result->isSuccess());
        Assert::null($result->getLastError());
    }

    #[Test]
    public function testPendingChanges(): void
    {
        $eow = new UnitOfWork($this->orm, Runner::outerTransaction());

        $entity = new Post();
        $entity->title = 'Test title';
        $entity->content = 'Test';

        $eow->persistState($entity);

        Assert::true($eow->hasPendingChanges());

        $result = $eow->run();
        Assert::true($eow->hasPendingChanges());
        Assert::false($result->isSuccess());
        Assert::instanceOf($result->getLastError(), RunnerException::class);

        $this->database->getDriver()->beginTransaction();

        $result = $result->retry();

        Assert::false($eow->hasPendingChanges());
        Assert::true($result->isSuccess());
        Assert::null($result->getLastError());
    }

    #[Test]
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

        Assert::true($result->isSuccess());
        Assert::null($result->getLastError());
        Assert::true($result2->isSuccess());
        Assert::null($result2->getLastError());

        $this->orm->getHeap()->clean();
        $stored = (new Select($this->orm, Post::class))
            ->wherePK($post1->id, $post2->id, $post3->id, $post4->id)
            ->fetchAll();

        Assert::same($stored[0]->title, 'Title1');
        Assert::same($stored[1]->title, 'Title2');
        Assert::same($stored[2]->title, 'Title3');
        Assert::same($stored[3]->title, 'Title4');
    }

    #[Test]
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
        Assert::false($result->isSuccess());
        Assert::instanceOf($result->getLastError(), RunnerException::class);
        Assert::null($post1->id);
        Assert::null($post2->id);

        // success transaction
        Assert::true($result2->isSuccess());
        Assert::null($result2->getLastError());
        Assert::notNull($post3->id);
        Assert::notNull($post4->id);

        $this->orm->getHeap()->clean();
        $stored = (new Select($this->orm, Post::class))->wherePK($post3->id, $post4->id)->fetchAll();

        Assert::same($stored[0]->title, 'Title3');
        Assert::same($stored[1]->title, 'Title4');
    }

    #[Test]
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

        Assert::false($result->isSuccess());
        Assert::instanceOf($result->getLastError(), RunnerException::class);

        // Node in the transaction, the state cleared
        Assert::null($this->orm->getHeap()->get($post1)->getState());

        // Node outside the transaction - the state not changed
        Assert::same($this->orm->getHeap()->get($post2)->getState()->getData(), ['title' => 'Title2', 'content' => 'Test2']);
    }

    #[Test]
    public function testCallSuccessRetryException(): void
    {
        $eow = new UnitOfWork($this->orm, Runner::outerTransaction());

        $post = new Post();
        $post->title = 'Title1';
        $post->content = 'Test1';

        $eow->persistState($post);

        $result = $eow->run();

        Assert::false($result->isSuccess());
        Assert::instanceOf($result->getLastError(), RunnerException::class);

        $this->database->getDriver()->beginTransaction();

        $result = $result->retry();

        Assert::true($result->isSuccess());
        Assert::null($result->getLastError());

        Expect::exception(SuccessTransactionRetryException::class);
        $result->retry();
    }

    #[Test]
    public function emptyUnitHasNoPendingChanges(): void
    {
        Assert::false((new UnitOfWork($this->orm))->hasPendingChanges());
    }

    #[Test]
    #[DataSet(['persistState', 'persist entity'])]
    #[DataSet(['persistDeferred', 'schedule entity storing'])]
    #[DataSet(['delete', 'schedule entity deletion'])]
    public function finishedUnitRejectsNewEntities(string $method, string $action): never
    {
        $uow = new UnitOfWork($this->orm);
        $uow->persistState($this->post('Title1'));
        $uow->run();

        Expect::exception(TransactionException::class)->withMessage("Can't $action. Transaction finished.");

        $uow->$method($this->post('Title2'));
    }

    #[Test]
    #[DataSet(['persistState', 'persist entity'])]
    #[DataSet(['persistDeferred', 'schedule entity storing'])]
    #[DataSet(['delete', 'schedule entity deletion'])]
    public function runningUnitRejectsNewEntities(string $method, string $action): void
    {
        $errors = [];
        $uow = new UnitOfWork($this->orm);
        $uow->setRunner($this->runnerWithHook(function () use ($uow, $method, &$errors): void {
            try {
                $uow->$method($this->post('Title2'));
            } catch (TransactionException $e) {
                $errors[] = $e->getMessage();
            }
        }));
        $uow->persistState($this->post('Title1'));

        $uow->run();

        Assert::same($errors, ["Can't $action. Transaction began."]);
    }

    #[Test]
    public function runningUnitCannotBeRunAgain(): void
    {
        $errors = [];
        $uow = new UnitOfWork($this->orm);
        $uow->setRunner($this->runnerWithHook(static function () use ($uow, &$errors): void {
            try {
                $uow->run();
            } catch (TransactionException $e) {
                $errors[] = $e->getMessage();
            }
        }));
        $uow->persistState($this->post('Title1'));

        $uow->run();

        Assert::same($errors, ["Can't run started transaction."]);
    }

    #[BeforeTest]
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

    private function post(string $title): Post
    {
        $post = new Post();
        $post->title = $title;
        $post->content = 'Test';
        return $post;
    }

    /**
     * @param \Closure(): void $hook Called once, while the first command runs.
     */
    private function runnerWithHook(\Closure $hook): RunnerInterface
    {
        return new class(Runner::innerTransaction(), $hook) implements RunnerInterface {
            public function __construct(
                private readonly RunnerInterface $runner,
                private ?\Closure $hook,
            ) {}

            public function run(CommandInterface $command): void
            {
                $hook = $this->hook;
                $this->hook = null;
                $hook === null or $hook();
                $this->runner->run($command);
            }

            public function complete(): void
            {
                $this->runner->complete();
            }

            public function count(): int
            {
                return $this->runner->count();
            }

            public function rollback(): void
            {
                $this->runner->rollback();
            }
        };
    }
}
