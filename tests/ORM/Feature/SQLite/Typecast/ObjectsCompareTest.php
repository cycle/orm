<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\SQLite\Typecast;

use Cycle\ORM\Heap\Node;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Feature\Common\BaseTest;
use Cycle\ORM\Tests\Feature\Common\Typecast\Fixture\Book;
use Cycle\ORM\Tests\Feature\Common\Typecast\Fixture\BookNestedStates;
use Cycle\ORM\Tests\Feature\Common\Typecast\Fixture\BookStates;
use Cycle\ORM\Tests\Traits\TableTrait;

final class ObjectsCompareTest extends BaseTest
{
    use TableTrait;

    public const DRIVER = 'sqlite';

    #[\Testo\Test]
    public function testCompare(): void
    {
        $book = new Book();
        $book->states = new BookStates(['foo']);
        $book->nested_states = new BookNestedStates(['bar']);

        $this->captureWriteQueries();
        $this->save($book);
        $this->assertNumWrites(1);

        $this->captureWriteQueries();
        $this->save($book);
        $this->assertNumWrites(0);

        \Testo\Assert::equals($book->id, 1);

        $this->orm->getHeap()->clean();
        $fetched = (new Select($this->orm, Book::class))->fetchOne();

        \Testo\Assert::equals($book->states, $fetched->states);
        \Testo\Assert::equals($book->nested_states, $fetched->nested_states);

        $fetched->states->states = ['foo', 'bar'];
        $fetched->nested_states->states[0]->title = 'baz';

        $this->captureWriteQueries();
        $this->save($fetched);
        $this->assertNumWrites(1);

        $this->captureWriteQueries();
        $this->save($fetched);
        $this->assertNumWrites(0);

        $this->orm->getHeap()->clean();
        $changed = (new Select($this->orm, Book::class))->fetchOne();

        \Testo\Assert::equals($changed->states, $fetched->states);
        \Testo\Assert::equals($changed->nested_states, $fetched->nested_states);
    }

    #[\Testo\Test]
    public function testCompareDateTime(): void
    {
        $book = new Book();
        $book->states = new BookStates(['foo']);
        $book->nested_states = new BookNestedStates(['bar']);

        $this->captureWriteQueries();
        $this->save($book);
        $this->assertNumWrites(1);

        $this->captureWriteQueries();
        $this->save($book);
        $this->assertNumWrites(0);

        $this->orm->getHeap()->clean();
        $data = (new Select($this->orm, Book::class))->fetchData();
        $data[0]['published_at'] = new \DateTime();

        $fetched = $this->orm->make('book', $data[0], Node::MANAGED, typecast: false);
        $fetched->published_at->setDate(2010, 1, 1);


        $this->captureWriteQueries();
        $this->save($fetched);
        $this->assertNumWrites(1);

        $this->captureWriteQueries();
        $this->save($fetched);
        $this->assertNumWrites(0);

        $this->orm->getHeap()->clean();
        $changed = (new Select($this->orm, Book::class))->fetchOne();

        $interval = $changed->published_at->diff($fetched->published_at);

        \Testo\Assert::instanceOf($interval, \DateInterval::class);
        \Testo\Assert::same($interval->y, 0);
        \Testo\Assert::same($interval->m, 0);
        \Testo\Assert::same($interval->d, 0);
    }

    #[\Testo\Lifecycle\BeforeTest]
    public function setUp(): void
    {
        parent::setUp();

        $this->makeTable('book', [
            'id' => 'primary',
            'states' => 'string',
            'nested_states' => 'string',
            'published_at' => 'datetime',
        ]);

        $this->orm = $this->withSchema(new Schema([
            Book::class => [
                SchemaInterface::ROLE => 'book',
                SchemaInterface::MAPPER => Mapper::class,
                SchemaInterface::DATABASE => 'default',
                SchemaInterface::TABLE => 'book',
                SchemaInterface::PRIMARY_KEY => 'id',
                SchemaInterface::COLUMNS => ['id', 'states', 'nested_states', 'published_at'],
                SchemaInterface::SCHEMA => [],
                SchemaInterface::TYPECAST => [
                    'id' => 'int',
                    'states' => [BookStates::class, 'cast'],
                    'nested_states' => [BookNestedStates::class, 'cast'],
                    'published_at' => 'datetime',
                ],
                SchemaInterface::RELATIONS => [],
            ],
        ]));
    }
}
