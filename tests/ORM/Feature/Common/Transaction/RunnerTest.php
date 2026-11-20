<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Transaction;

use Cycle\ORM\Exception\RunnerException;
use Cycle\ORM\Tests\Fixtures\TestCommand;
use Cycle\ORM\Transaction\Runner;
use Cycle\Testing\ORM\OrmTestCase;

class RunnerTest extends OrmTestCase
{

    // Mode OPEN_TRANSACTION

    #[\Testo\Test]
    public function testInnerTransactionRun(): void
    {
        \Testo\Assert::same($this->database->getDriver()->getTransactionLevel(), 0);

        Runner::innerTransaction()->run(new TestCommand($this->getDatabase()));

        \Testo\Assert::same($this->database->getDriver()->getTransactionLevel(), 1);
    }

    /**
     * The opened transaction should be commited
     */
    #[\Testo\Test]
    public function testInnerTransactionComplete(): void
    {
        $runner = Runner::innerTransaction();
        $runner->run(new TestCommand($this->getDatabase()));

        \Testo\Assert::same($this->database->getDriver()->getTransactionLevel(), 1);

        $runner->complete();

        \Testo\Assert::same($this->database->getDriver()->getTransactionLevel(), 0);
    }

    /**
     * The opened transaction should be rollbacked
     */
    #[\Testo\Test]
    public function testInnerTransactionRollback(): void
    {
        $runner = Runner::innerTransaction();
        $runner->run(new TestCommand($this->getDatabase()));

        \Testo\Assert::same($this->database->getDriver()->getTransactionLevel(), 1);

        $runner->rollback();

        \Testo\Assert::same($this->database->getDriver()->getTransactionLevel(), 0);
    }

    // Mode CONTINUE_TRANSACTION

    #[\Testo\Test]
    public function testContinueTransactionRun(): void
    {
        $this->database->getDriver()->beginTransaction();

        \Testo\Assert::same($this->database->getDriver()->getTransactionLevel(), 1);

        Runner::outerTransaction()->run(new TestCommand($this->getDatabase()));

        \Testo\Assert::same($this->database->getDriver()->getTransactionLevel(), 1);
    }

    #[\Testo\Test]
    public function testContinueTransactionWithNotOpenedTransaction(): void
    {
        \Testo\Expect::exception(RunnerException::class);

        Runner::outerTransaction()->run(new TestCommand($this->getDatabase()));
    }

    #[\Testo\Test]
    public function testContinueTransactionComplete(): void
    {
        $this->database->getDriver()->beginTransaction();

        \Testo\Assert::same($this->database->getDriver()->getTransactionLevel(), 1);

        $runner = Runner::outerTransaction();
        $runner->run(new TestCommand($this->getDatabase()));
        $runner->complete();

        \Testo\Assert::same($this->database->getDriver()->getTransactionLevel(), 1);
    }

    #[\Testo\Test]
    public function testContinueTransactionRollback(): void
    {
        $this->database->getDriver()->beginTransaction();

        \Testo\Assert::same($this->database->getDriver()->getTransactionLevel(), 1);

        $runner = Runner::outerTransaction();
        $runner->run(new TestCommand($this->getDatabase()));
        $runner->rollback();

        \Testo\Assert::same($this->database->getDriver()->getTransactionLevel(), 1);
    }

    // Mode IGNORE_TRANSACTION

    #[\Testo\Test]
    public function testIgnoreTransactionRun(): void
    {
        \Testo\Assert::same($this->database->getDriver()->getTransactionLevel(), 0);

        Runner::outerTransaction(strict: false)->run(new TestCommand($this->getDatabase()));

        \Testo\Assert::same($this->database->getDriver()->getTransactionLevel(), 0);
    }

    #[\Testo\Test]
    public function testIgnoreTransactionComplete(): void
    {
        $this->database->getDriver()->beginTransaction();
        \Testo\Assert::same($this->database->getDriver()->getTransactionLevel(), 1);

        $runner = Runner::outerTransaction(strict: false);
        $runner->run(new TestCommand($this->getDatabase()));
        $runner->complete();

        \Testo\Assert::same($this->database->getDriver()->getTransactionLevel(), 1);
    }

    #[\Testo\Test]
    public function testIgnoreTransactionRollback(): void
    {
        $this->database->getDriver()->beginTransaction();
        \Testo\Assert::same($this->database->getDriver()->getTransactionLevel(), 1);

        $runner = Runner::outerTransaction(strict: false);
        $runner->run(new TestCommand($this->getDatabase()));
        $runner->rollback();

        \Testo\Assert::same($this->database->getDriver()->getTransactionLevel(), 1);
    }

    #[\Testo\Lifecycle\BeforeTest]
    public function setUp(): void
    {
    }
}
