<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Typecast;

use Cycle\ORM\Heap\Node;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Functional\Driver\Common\Typecast\Fixture\Book;
use Cycle\ORM\Tests\Functional\Driver\Common\Typecast\Fixture\BookNestedStates;
use Cycle\ORM\Tests\Functional\Driver\Common\Typecast\Fixture\BookStates;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Traits\TableTrait;
use DateInterval;
use DateTime;

final class ObjectsCompareTest extends BaseTest
{
    public const DRIVER = 'sqlite';

    use TableTrait;

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

        $this->assertEquals(1, $book->id);

        $this->orm->getHeap()->clean();
        $fetched = (new Select($this->orm, Book::class))->fetchOne();

        $this->assertEquals($fetched->states, $book->states);
        $this->assertEquals($fetched->nested_states, $book->nested_states);

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

        $this->assertEquals($fetched->states, $changed->states);
        $this->assertEquals($fetched->nested_states, $changed->nested_states);
    }

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
        $data[0]['published_at'] = new DateTime();

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

        $this->assertInstanceOf(DateInterval::class, $interval);
        $this->assertSame(0, $interval->y);
        $this->assertSame(0, $interval->m);
        $this->assertSame(0, $interval->d);
    }
}
