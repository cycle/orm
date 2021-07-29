<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Relation\JTI\Trait;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Relation\JTI\Fixture\Employee;
use Cycle\ORM\Tests\Relation\JTI\Fixture\Engineer;
use Cycle\ORM\Tests\Relation\JTI\Fixture\Programator;
use Cycle\ORM\Transaction;

trait PersistTrait
{
    public function testProgramatorNoChanges(): void
    {
        $programator = (new Select($this->orm, Programator::class))->wherePK(2)->fetchOne();

        $this->captureWriteQueries();
        $this->save($programator);
        $this->assertNumWrites(0);
    }

    public function testChangeAndPersistProgramator(): void
    {
        /** @var Programator $programator */
        $programator = (new Select($this->orm, Programator::class))->wherePK(2)->fetchOne();
        $programator->language = 'Kotlin';

        $this->captureWriteQueries();
        $this->save($programator);
        $this->assertNumWrites(1);

        $this->captureWriteQueries();
        $this->save($programator);
        $this->assertNumWrites(0);

        /** @var Programator $programator */
        $programator = (new Select($this->orm->withHeap(new Heap()), Programator::class))->wherePK(2)->fetchOne();
        $this->assertSame('Kotlin', $programator->language);
    }

    public function testChangeParentsFieldsAndPersistProgramator(): void
    {
        /** @var Programator $programator */
        $programator = (new Select($this->orm, Programator::class))->wherePK(2)->fetchOne();
        $programator->language = 'Kotlin';
        $programator->level = 99;
        $programator->name = 'Thomas';

        $this->captureWriteQueries();
        $this->save($programator);
        $this->assertNumWrites(3);

        $this->captureWriteQueries();
        $this->save($programator);
        $this->assertNumWrites(0);

        /** @var Programator $programator */
        $programator = (new Select($this->orm->withHeap(new Heap()), Programator::class))->wherePK(2)->fetchOne();
        $this->assertSame('Kotlin', $programator->language);
        $this->assertSame(99, $programator->level);
        $this->assertSame('Thomas', $programator->name);
    }

    public function testCreateProgramator(): void
    {
        $programator = new Programator();
        $programator->name = 'Merlin';
        $programator->level = 50;
        $programator->language = 'VanillaJS';

        $this->captureWriteQueries();
        $this->save($programator);
        $this->assertNumWrites(3);

        $this->captureWriteQueries();
        $this->save($programator);
        $this->assertNumWrites(0);

        /** @var Programator $programator */
        $programator = (new Select($this->orm->withHeap(new Heap()), Programator::class))
            ->wherePK($programator->id)
            ->fetchOne();
        $this->assertSame('Merlin', $programator->name);
        $this->assertSame(50, $programator->level);
        $this->assertSame('VanillaJS', $programator->language);
    }

    public function testRemoveEngineer(): void
    {
        /** @var Engineer $engineer */
        $engineer = (new Select($this->orm, Engineer::class))->wherePK(2)->fetchOne();

        $this->captureWriteQueries();
        (new Transaction($this->orm))->delete($engineer)->run();
        $this->assertNumWrites(1);

        $this->captureWriteQueries();
        (new Transaction($this->orm))->delete($engineer)->run();
        $this->assertNumWrites(0);

        $this->assertNull((new Select($this->orm, Programator::class))->wherePK(2)->fetchOne());
        $this->assertNull((new Select($this->orm, Engineer::class))->wherePK(2)->fetchOne());
        $this->assertNotNull((new Select($this->orm, Employee::class))->wherePK(2)->fetchOne());
    }
}