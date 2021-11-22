<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\Book;
use Cycle\ORM\Tests\Fixtures\BookNestedStates;
use Cycle\ORM\Tests\Fixtures\BookStates;
use Cycle\ORM\Tests\Traits\TableTrait;
use DateInterval;
use DateTime;

abstract class ObjectsCompareTest extends BaseTest
{
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
                Schema::ROLE => 'book',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'book',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'states', 'nested_states', 'published_at'],
                Schema::SCHEMA => [],
                Schema::TYPECAST => [
                    'id' => 'int',
                    'states' => [BookStates::class, 'cast'],
                    'nested_states' => [BookNestedStates::class, 'cast'],
                    'published_at' => 'datetime',
                ],
                Schema::RELATIONS => [],
            ],
        ]));
    }

    public function testCompare(): void
    {
        $book = new Book();
        $book->states = new BookStates(['foo']);
        $book->nested_states = new BookNestedStates(['bar']);

        $this->save($book);

        $this->assertEquals(1, $book->id);

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Select($this->orm, Book::class);
        $fetched = $selector->fetchOne();

        $this->assertEquals($fetched->states, $book->states);
        $this->assertEquals($fetched->nested_states, $book->nested_states);

        $fetched->states->states = ['foo', 'bar'];
        $fetched->nested_states->states[0]->title = 'baz';

        $this->save($fetched);

        $selector = new Select($this->orm->withHeap(new Heap()), Book::class);
        $changed = $selector->fetchOne();

        $this->assertEquals($fetched->states, $changed->states);
        $this->assertEquals($fetched->nested_states, $changed->nested_states);
    }

    public function testCompareDateTime(): void
    {
        $this->orm = $this->orm->withHeap(new Heap());

        $book = new Book();
        $book->states = new BookStates(['foo']);
        $book->nested_states = new BookNestedStates(['bar']);

        $this->save($book);

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Select($this->orm, Book::class);
        $data = $selector->fetchData();
        $data[0]['published_at'] = new DateTime();

        $fetched = $this->orm->make('book', $data[0], Node::MANAGED);
        $fetched->published_at->setDate(2010, 1, 1);

        $this->save($fetched);

        $selector = new Select($this->orm->withHeap(new Heap()), Book::class);
        $changed = $selector->fetchOne();

        $interval = $changed->published_at->diff($fetched->published_at);

        $this->assertInstanceOf(DateInterval::class, $interval);
        $this->assertEquals(0, $interval->y);
        $this->assertEquals(0, $interval->m);
        $this->assertEquals(0, $interval->d);
    }
}
