<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Transaction;

use Cycle\ORM\Exception\RunnerException;
use Cycle\ORM\Tests\Fixtures\TestCommand;
use Cycle\ORM\Transaction\Runner;
use Cycle\Testing\ORM\OrmTestCase;
use Testo\Assert;
use Testo\Assert\ExpectException;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

final class RunnerTest extends OrmTestCase
{
    // Mode OPEN_TRANSACTION

    #[Test]
    public function testInnerTransactionRun(): void
    {
        Assert::same($this->database->getDriver()->getTransactionLevel(), 0);

        Runner::innerTransaction()->run(new TestCommand($this->getDatabase()));

        Assert::same($this->database->getDriver()->getTransactionLevel(), 1);
    }

    /**
     * The opened transaction should be commited
     */
    #[Test]
    public function testInnerTransactionComplete(): void
    {
        $runner = Runner::innerTransaction();
        $runner->run(new TestCommand($this->getDatabase()));

        Assert::same($this->database->getDriver()->getTransactionLevel(), 1);

        $runner->complete();

        Assert::same($this->database->getDriver()->getTransactionLevel(), 0);
    }

    /**
     * The opened transaction should be rollbacked
     */
    #[Test]
    public function testInnerTransactionRollback(): void
    {
        $runner = Runner::innerTransaction();
        $runner->run(new TestCommand($this->getDatabase()));

        Assert::same($this->database->getDriver()->getTransactionLevel(), 1);

        $runner->rollback();

        Assert::same($this->database->getDriver()->getTransactionLevel(), 0);
    }

    // Mode CONTINUE_TRANSACTION

    #[Test]
    public function testContinueTransactionRun(): void
    {
        $this->database->getDriver()->beginTransaction();

        Assert::same($this->database->getDriver()->getTransactionLevel(), 1);

        Runner::outerTransaction()->run(new TestCommand($this->getDatabase()));

        Assert::same($this->database->getDriver()->getTransactionLevel(), 1);
    }

    #[Test]
    #[ExpectException(RunnerException::class)]
    public function testContinueTransactionWithNotOpenedTransaction(): void
    {
        Runner::outerTransaction()->run(new TestCommand($this->getDatabase()));
    }

    #[Test]
    public function testContinueTransactionComplete(): void
    {
        $this->database->getDriver()->beginTransaction();

        Assert::same($this->database->getDriver()->getTransactionLevel(), 1);

        $runner = Runner::outerTransaction();
        $runner->run(new TestCommand($this->getDatabase()));
        $runner->complete();

        Assert::same($this->database->getDriver()->getTransactionLevel(), 1);
    }

    #[Test]
    public function testContinueTransactionRollback(): void
    {
        $this->database->getDriver()->beginTransaction();

        Assert::same($this->database->getDriver()->getTransactionLevel(), 1);

        $runner = Runner::outerTransaction();
        $runner->run(new TestCommand($this->getDatabase()));
        $runner->rollback();

        Assert::same($this->database->getDriver()->getTransactionLevel(), 1);
    }

    // Mode IGNORE_TRANSACTION

    #[Test]
    public function testIgnoreTransactionRun(): void
    {
        Assert::same($this->database->getDriver()->getTransactionLevel(), 0);

        Runner::outerTransaction(strict: false)->run(new TestCommand($this->getDatabase()));

        Assert::same($this->database->getDriver()->getTransactionLevel(), 0);
    }

    #[Test]
    public function testIgnoreTransactionComplete(): void
    {
        $this->database->getDriver()->beginTransaction();
        Assert::same($this->database->getDriver()->getTransactionLevel(), 1);

        $runner = Runner::outerTransaction(strict: false);
        $runner->run(new TestCommand($this->getDatabase()));
        $runner->complete();

        Assert::same($this->database->getDriver()->getTransactionLevel(), 1);
    }

    #[Test]
    public function testIgnoreTransactionRollback(): void
    {
        $this->database->getDriver()->beginTransaction();
        Assert::same($this->database->getDriver()->getTransactionLevel(), 1);

        $runner = Runner::outerTransaction(strict: false);
        $runner->run(new TestCommand($this->getDatabase()));
        $runner->rollback();

        Assert::same($this->database->getDriver()->getTransactionLevel(), 1);
    }

    #[BeforeTest]
    public function setUp(): void {}
}
