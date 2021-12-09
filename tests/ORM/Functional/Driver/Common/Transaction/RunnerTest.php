<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Transaction;

use Cycle\ORM\Exception\RunnerException;
use Cycle\ORM\Tests\Fixtures\TestCommand;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Traits\TableTrait;
use Cycle\ORM\Transaction\Runner;

abstract class RunnerTest extends BaseTest
{
    use TableTrait;

    public function setUp(): void
    {
        parent::setUp();
    }

    // Mode OPEN_TRANSACTION

    public function testInnerTransactionRun(): void
    {
        $this->assertSame(0, $this->getDriver()->getTransactionLevel());

        Runner::innerTransaction()->run(new TestCommand($this->getDatabase()));

        $this->assertSame(1, $this->getDriver()->getTransactionLevel());
    }

    /**
     * The opened transaction should be commited
     */
    public function testInnerTransactionComplete(): void
    {
        $runner = Runner::innerTransaction();
        $runner->run(new TestCommand($this->getDatabase()));

        $this->assertSame(1, $this->getDriver()->getTransactionLevel());

        $runner->complete();

        $this->assertSame(0, $this->getDriver()->getTransactionLevel());
    }

    /**
     * The opened transaction should be rollbacked
     */
    public function testInnerTransactionRollback(): void
    {
        $runner = Runner::innerTransaction();
        $runner->run(new TestCommand($this->getDatabase()));

        $this->assertSame(1, $this->getDriver()->getTransactionLevel());

        $runner->rollback();

        $this->assertSame(0, $this->getDriver()->getTransactionLevel());
    }

    // Mode CONTINUE_TRANSACTION

    public function testContinueTransactionRun(): void
    {
        $this->getDriver()->beginTransaction();

        $this->assertSame(1, $this->getDriver()->getTransactionLevel());

        Runner::outerTransaction()->run(new TestCommand($this->getDatabase()));

        $this->assertSame(1, $this->getDriver()->getTransactionLevel());
    }

    public function testContinueTransactionWithNotOpenedTransaction(): void
    {
        $this->expectException(RunnerException::class);

        Runner::outerTransaction()->run(new TestCommand($this->getDatabase()));
    }

    public function testContinueTransactionComplete(): void
    {
        $this->getDriver()->beginTransaction();

        $this->assertSame(1, $this->getDriver()->getTransactionLevel());

        $runner = Runner::outerTransaction();
        $runner->run(new TestCommand($this->getDatabase()));
        $runner->complete();

        $this->assertSame(1, $this->getDriver()->getTransactionLevel());
    }

    public function testContinueTransactionRollback(): void
    {
        $this->getDriver()->beginTransaction();

        $this->assertSame(1, $this->getDriver()->getTransactionLevel());

        $runner = Runner::outerTransaction();
        $runner->run(new TestCommand($this->getDatabase()));
        $runner->rollback();

        $this->assertSame(1, $this->getDriver()->getTransactionLevel());
    }

    // Mode IGNORE_TRANSACTION

    public function testIgnoreTransactionRun(): void
    {
        $this->assertSame(0, $this->getDriver()->getTransactionLevel());

        (Runner::ignoreTransaction())->run(new TestCommand($this->getDatabase()));

        $this->assertSame(0, $this->getDriver()->getTransactionLevel());
    }

    public function testIgnoreTransactionComplete(): void
    {
        $this->getDriver()->beginTransaction();
        $this->assertSame(1, $this->getDriver()->getTransactionLevel());

        $runner = Runner::ignoreTransaction();
        $runner->run(new TestCommand($this->getDatabase()));
        $runner->complete();

        $this->assertSame(1, $this->getDriver()->getTransactionLevel());
    }

    public function testIgnoreTransactionRollback(): void
    {
        $this->getDriver()->beginTransaction();
        $this->assertSame(1, $this->getDriver()->getTransactionLevel());

        $runner = Runner::ignoreTransaction();
        $runner->run(new TestCommand($this->getDatabase()));
        $runner->rollback();

        $this->assertSame(1, $this->getDriver()->getTransactionLevel());
    }
}
