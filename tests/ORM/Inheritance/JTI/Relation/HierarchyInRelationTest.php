<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Inheritance\JTI\Relation;

use Cycle\ORM\Exception\LoaderException;
use Cycle\ORM\Mapper\PromiseMapper;
use Cycle\ORM\Relation;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Inheritance\Fixture\Book;
use Cycle\ORM\Tests\Inheritance\Fixture\EBook;
use Cycle\ORM\Tests\Inheritance\Fixture\Employee;
use Cycle\ORM\Tests\Inheritance\Fixture\Engineer;
use Cycle\ORM\Tests\Inheritance\Fixture\HtmlPage;
use Cycle\ORM\Tests\Inheritance\Fixture\Human;
use Cycle\ORM\Tests\Inheritance\Fixture\Manager;
use Cycle\ORM\Tests\Inheritance\Fixture\MarkdownPage;
use Cycle\ORM\Tests\Inheritance\Fixture\Page;
use Cycle\ORM\Tests\Inheritance\Fixture\Programator;
use Cycle\ORM\Tests\Inheritance\Fixture\Tool;
use Cycle\ORM\Tests\Inheritance\JTI\JtiBaseTest;

abstract class HierarchyInRelationTest extends JtiBaseTest
{
    protected const
        DEFAULT_MAPPER = PromiseMapper::class;
    protected const
        HUMAN_ROLE = 'human';
    protected const
        EMPLOYEE_ROLE = 'employee';
    protected const
        ENGINEER_ROLE = 'engineer';
    protected const
        MANAGER_ROLE = 'manager';
    protected const
        PROGRAMATOR_ROLE = 'programator';
    protected const
        BOOK_ROLE = 'book';
    protected const
        EBOOK_ROLE = 'ebook';
    protected const
        PAGE_ROLE = 'page';
    protected const
        HTML_PAGE_ROLE = 'html_page';
    protected const
        MARKDOWN_PAGE_ROLE = 'markdown_page';
    protected const
        TOOL_ROLE = 'tool';
    protected const
        HUMAN_1 = ['id' => 1, 'birthday' => null];
    protected const
        HUMAN_2 = ['id' => 2, 'birthday' => null];
    protected const
        HUMAN_3 = ['id' => 3, 'birthday' => null];
    protected const
        HUMAN_4 = ['id' => 4, 'birthday' => null];
    protected const
        EMPLOYEE_1 = ['id' => 1, 'name' => 'John', 'book_id' => 3];
    protected const
        EMPLOYEE_2 = ['id' => 2, 'name' => 'Anton', 'book_id' => 4];
    protected const
        EMPLOYEE_3 = ['id' => 3, 'name' => 'Kentarius', 'book_id' => 1];
    protected const
        EMPLOYEE_4 = ['id' => 4, 'name' => 'Valeriy', 'book_id' => null];
    protected const
        ENGINEER_2 = ['id' => 2, 'level' => 8, 'tech_book_id' => 3];
    protected const
        ENGINEER_4 = ['id' => 4, 'level' => 10, 'tech_book_id' => 4];
    protected const
        PROGRAMATOR_2 = ['id' => 2, 'language' => 'php'];
    protected const
        PROGRAMATOR_4 = ['id' => 4, 'language' => 'go'];
    protected const
        MANAGER_1 = ['id' => 1, 'rank' => 'top'];
    protected const
        MANAGER_3 = ['id' => 3, 'rank' => 'bottom'];
    protected const
        BOOK_1 = ['id' => 1, 'title' => 'PHP manual'];
    protected const
        BOOK_2 = ['id' => 2, 'title' => 'Best mentor'];
    protected const
        BOOK_3 = ['id' => 3, 'title' => 'Wikipedia vol.42'];
    protected const
        BOOK_4 = ['id' => 4, 'title' => 'How to be Foo when you are Bar'];
    protected const
        EBOOK_3 = ['id' => 3, 'block_id' => 1, 'url' => 'https://wikipedia.org/ebook/42'];
    protected const
        EBOOK_4 = ['id' => 4, 'block_id' => null, 'url' => 'localhost'];
    protected const
        PAGE_1 = ['id' => 1, 'title' => 'Page 1', 'content' => 'Some content...', 'owner_id' => 2, 'block_id' => 1];
    protected const
        PAGE_2 = ['id' => 2, 'title' => 'Page 2', 'content' => 'Some content...', 'owner_id' => null, 'block_id' => 1];
    protected const
        PAGE_3 = ['id' => 3, 'title' => 'Page 3', 'content' => 'Some content...', 'owner_id' => 2, 'block_id' => 1];
    protected const
        PAGE_4 = ['id' => 4, 'title' => 'Page 4', 'content' => 'Some content...', 'owner_id' => 1, 'block_id' => null];
    protected const
        PAGE_5 = ['id' => 5, 'title' => 'Page 5', 'content' => 'Some content...', 'owner_id' => 1, 'block_id' => null];
    protected const
        MARKDOWN_PAGE_1 = ['id' => 1];
    protected const
        MARKDOWN_PAGE_5 = ['id' => 5];
    protected const
        HTML_PAGE_2 = ['id' => 2];
    protected const
        TOOL_1 = ['id' => 1, 'engineer_id' => 2, 'title' => 'Hammer'];
    protected const
        TOOL_2 = ['id' => 2, 'engineer_id' => 2, 'title' => 'Notebook'];
    protected const
        TOOL_3 = ['id' => 3, 'engineer_id' => 2, 'title' => 'Notepad'];
    protected const
        TOOL_4 = ['id' => 4, 'engineer_id' => 2, 'title' => 'IDE'];
    protected const
        EMPLOYEE_1_LOADED = self::EMPLOYEE_1 + ['book' => self::BOOK_3];
    protected const
        EMPLOYEE_2_LOADED = self::EMPLOYEE_2 + ['book' => self::BOOK_2];
    protected const
        EMPLOYEE_3_LOADED = self::EMPLOYEE_3 + ['book' => self::BOOK_1];
    protected const
        EMPLOYEE_4_LOADED = self::EMPLOYEE_4 + ['book' => null];
    protected const
        ENGINEER_2_LOADED = self::ENGINEER_2 + self::EMPLOYEE_2_LOADED;
    protected const
        ENGINEER_4_LOADED = self::ENGINEER_4 + self::EMPLOYEE_4_LOADED;
    protected const
        PROGRAMATOR_2_LOADED = self::PROGRAMATOR_2 + self::ENGINEER_2_LOADED;
    protected const
        PROGRAMATOR_4_LOADED = self::PROGRAMATOR_4 + self::ENGINEER_4_LOADED;
    protected const
        MANAGER_1_LOADED = self::MANAGER_1 + self::EMPLOYEE_1_LOADED;
    protected const
        MANAGER_3_LOADED = self::MANAGER_3 + self::EMPLOYEE_3_LOADED;
    protected const
        EMPLOYEE_ALL_LOADED = [
            self::EMPLOYEE_1_LOADED,
            self::EMPLOYEE_2_LOADED,
            self::EMPLOYEE_3_LOADED,
            self::EMPLOYEE_4_LOADED,
        ];
    protected const
        EMPLOYEE_INHERITED_LOADED = [
            self::MANAGER_1_LOADED,
            self::PROGRAMATOR_2_LOADED,
            self::MANAGER_3_LOADED,
            self::PROGRAMATOR_4_LOADED,
        ];
    protected const
        ENGINEER_ALL_LOADED = [self::ENGINEER_2_LOADED, self::ENGINEER_4_LOADED];
    protected const
        PROGRAMATOR_ALL_LOADED = [self::PROGRAMATOR_2_LOADED, self::PROGRAMATOR_4_LOADED];
    protected const
        MANAGER_ALL_LOADED = [self::MANAGER_1_LOADED, self::MANAGER_3_LOADED];

    public function setUp(): void
    {
        JtiBaseTest::setUp();

        $this->makeTable('human_table', [
            'id' => 'integer',
            'birthday' => 'date,nullable',
        ], pk: ['id']);
        $this->makeTable('employee_table', [
            'id' => 'integer',
            'name' => 'string',
            'book_id' => 'integer,nullable',
        ], fk: [
            'id' => ['table' => 'human_table', 'column' => 'id'],
            'book_id' => ['table' => 'book_table', 'column' => 'id'],
        ], pk: ['id']);
        $this->makeTable('engineer_table', [
            'id' => 'integer',
            'level' => 'integer',
            'tech_book_id' => 'integer,nullable',
        ], fk: [
            'id' => ['table' => 'employee_table', 'column' => 'id'],
        ], pk: ['id']);
        $this->makeTable('programator_table', [
            'id' => 'integer',
            'language' => 'string',
        ], fk: [
            'id' => ['table' => 'engineer_table', 'column' => 'id'],
        ], pk: ['id']);
        $this->makeTable('manager_table', [
            'id' => 'integer',
            'rank' => 'string',
        ], fk: [
            'id' => ['table' => 'employee_table', 'column' => 'id'],
        ], pk: ['id']);
        $this->makeTable('book_table', [
            'id' => 'integer',
            'title' => 'string',
        ], pk: ['id']);
        $this->makeTable('ebook_table', [
            'id' => 'integer',
            'block_id' => 'integer, nullable',
            'url' => 'string',
        ], fk: [
            'id' => ['table' => 'book_table', 'column' => 'id'],
        ], pk: ['id']);
        $this->makeTable('page_table', [
            'id' => 'integer',
            'block_id' => 'integer,nullable',
            'owner_id' => 'integer,nullable',
            'title' => 'string',
            'content' => 'string',
        ], fk: [
            'id' => ['table' => 'book_table', 'column' => 'id'],
        ], pk: ['id']);
        $this->makeTable('html_page_table', [
            'id' => 'integer',
        ], fk: [
            'id' => ['table' => 'page_table', 'column' => 'id'],
        ], pk: ['id']);
        $this->makeTable('markdown_page_table', [
            'id' => 'integer',
        ], fk: [
            'id' => ['table' => 'page_table', 'column' => 'id'],
        ], pk: ['id']);
        $this->makeTable('tool_table', [
            'id' => 'integer, primary',
            'engineer_id' => 'integer',
            'title' => 'string',
        ], pk: ['id']);

        $this->getDatabase()->table('human_table')->insertMultiple(
            array_keys(static::HUMAN_1),
            [
                self::HUMAN_1,
                self::HUMAN_2,
                self::HUMAN_3,
                self::HUMAN_4,
            ]
        );
        $this->getDatabase()->table('employee_table')->insertMultiple(
            array_keys(static::EMPLOYEE_1),
            [
                self::EMPLOYEE_1,
                self::EMPLOYEE_2,
                self::EMPLOYEE_3,
                self::EMPLOYEE_4,
            ]
        );
        $this->getDatabase()->table('engineer_table')->insertMultiple(
            array_keys(static::ENGINEER_2),
            [
                self::ENGINEER_2,
                self::ENGINEER_4,
            ]
        );
        $this->getDatabase()->table('programator_table')->insertMultiple(
            array_keys(static::PROGRAMATOR_2),
            [
                self::PROGRAMATOR_2,
                self::PROGRAMATOR_4,
            ]
        );
        $this->getDatabase()->table('manager_table')->insertMultiple(
            array_keys(static::MANAGER_1),
            [
                self::MANAGER_1,
                self::MANAGER_3,
            ]
        );
        $this->getDatabase()->table('book_table')->insertMultiple(
            array_keys(static::BOOK_1),
            [
                self::BOOK_1,
                self::BOOK_2,
                self::BOOK_3,
                self::BOOK_4,
            ]
        );
        $this->getDatabase()->table('ebook_table')->insertMultiple(
            array_keys(static::EBOOK_3),
            [
                self::EBOOK_3,
                self::EBOOK_4,
            ]
        );
        $this->getDatabase()->table('page_table')->insertMultiple(
            array_keys(static::PAGE_1),
            [
                self::PAGE_1,
                self::PAGE_2,
                self::PAGE_3,
                self::PAGE_4,
                self::PAGE_5,
            ]
        );
        $this->getDatabase()->table('html_page_table')->insertMultiple(
            array_keys(static::HTML_PAGE_2),
            [
                static::HTML_PAGE_2,
            ]
        );
        $this->getDatabase()->table('markdown_page_table')->insertMultiple(
            array_keys(static::MARKDOWN_PAGE_1),
            [
                static::MARKDOWN_PAGE_1,
                static::MARKDOWN_PAGE_5,
            ]
        );
        $this->getDatabase()->table('tool_table')->insertMultiple(
            array_keys(static::TOOL_1),
            [
                self::TOOL_1,
                self::TOOL_2,
                self::TOOL_3,
                self::TOOL_4,
            ]
        );
    }

    protected function getSchemaArray(): array
    {
        return [
            static::HUMAN_ROLE => [
                SchemaInterface::ENTITY => Human::class,
                SchemaInterface::MAPPER => static::DEFAULT_MAPPER,
                SchemaInterface::DATABASE => 'default',
                SchemaInterface::TABLE => 'human_table',
                SchemaInterface::PRIMARY_KEY => 'id',
                SchemaInterface::COLUMNS => ['id', 'birthday'],
                SchemaInterface::TYPECAST => ['id' => 'int'],
            ],
            static::EMPLOYEE_ROLE => [
                SchemaInterface::ENTITY => Employee::class,
                SchemaInterface::MAPPER => static::DEFAULT_MAPPER,
                SchemaInterface::DATABASE => 'default',
                SchemaInterface::TABLE => 'employee_table',
                SchemaInterface::PARENT => static::HUMAN_ROLE,
                SchemaInterface::PRIMARY_KEY => 'id',
                SchemaInterface::COLUMNS => ['id', 'name', 'book_id'],
                SchemaInterface::TYPECAST => ['id' => 'int', 'book_id' => 'int'],
                SchemaInterface::RELATIONS => [
                    'book' => [
                        Relation::TYPE => Relation::REFERS_TO,
                        Relation::TARGET => static::BOOK_ROLE,
                        Relation::LOAD => Relation::LOAD_EAGER,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::NULLABLE => true,
                            Relation::INNER_KEY => 'book_id',
                            Relation::OUTER_KEY => 'id',
                        ],
                    ],
                ],
            ],
            static::ENGINEER_ROLE => [
                SchemaInterface::ENTITY => Engineer::class,
                SchemaInterface::MAPPER => static::DEFAULT_MAPPER,
                SchemaInterface::DATABASE => 'default',
                SchemaInterface::TABLE => 'engineer_table',
                SchemaInterface::PARENT => static::EMPLOYEE_ROLE,
                SchemaInterface::PRIMARY_KEY => 'id',
                SchemaInterface::COLUMNS => ['id', 'level', 'tech_book_id'],
                SchemaInterface::TYPECAST => ['id' => 'int', 'level' => 'int', 'tech_book_id' => 'int'],
                SchemaInterface::SCHEMA => [],
                SchemaInterface::RELATIONS => [
                    'tech_book' => [
                        Relation::TYPE => Relation::REFERS_TO,
                        Relation::TARGET => static::EBOOK_ROLE,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::NULLABLE => true,
                            Relation::INNER_KEY => 'tech_book_id',
                            Relation::OUTER_KEY => 'id',
                        ],
                    ],
                    'tools' => [
                        Relation::TYPE => Relation::HAS_MANY,
                        Relation::TARGET => static::TOOL_ROLE,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::NULLABLE => false,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'engineer_id',
                        ],
                    ],
                ],
            ],
            static::PROGRAMATOR_ROLE => [
                SchemaInterface::ENTITY => Programator::class,
                SchemaInterface::MAPPER => static::DEFAULT_MAPPER,
                SchemaInterface::DATABASE => 'default',
                SchemaInterface::TABLE => 'programator_table',
                SchemaInterface::PARENT => static::ENGINEER_ROLE,
                SchemaInterface::PRIMARY_KEY => 'id',
                SchemaInterface::COLUMNS => ['id', 'language'],
                SchemaInterface::TYPECAST => ['id' => 'int'],
            ],
            static::MANAGER_ROLE => [
                SchemaInterface::ENTITY => Manager::class,
                SchemaInterface::MAPPER => static::DEFAULT_MAPPER,
                SchemaInterface::DATABASE => 'default',
                SchemaInterface::TABLE => 'manager_table',
                SchemaInterface::PARENT => static::EMPLOYEE_ROLE,
                SchemaInterface::PRIMARY_KEY => 'id',
                SchemaInterface::COLUMNS => ['id', 'rank'],
                SchemaInterface::TYPECAST => ['id' => 'int'],
            ],
            static::BOOK_ROLE => [
                SchemaInterface::ENTITY => Book::class,
                SchemaInterface::MAPPER => static::DEFAULT_MAPPER,
                SchemaInterface::DATABASE => 'default',
                SchemaInterface::TABLE => 'book_table',
                SchemaInterface::PRIMARY_KEY => 'id',
                SchemaInterface::COLUMNS => ['id', 'title'],
                SchemaInterface::TYPECAST => ['id' => 'int'],
            ],
            static::EBOOK_ROLE => [
                SchemaInterface::ENTITY => EBook::class,
                SchemaInterface::MAPPER => static::DEFAULT_MAPPER,
                SchemaInterface::DATABASE => 'default',
                SchemaInterface::TABLE => 'ebook_table',
                SchemaInterface::PARENT => static::BOOK_ROLE,
                SchemaInterface::PRIMARY_KEY => 'id',
                SchemaInterface::COLUMNS => ['id', 'url', 'block_id'],
                SchemaInterface::TYPECAST => ['id' => 'int', 'block_id' => 'int'],
                SchemaInterface::SCHEMA => [],
                SchemaInterface::RELATIONS => [
                    'pages' => [
                        Relation::TYPE => Relation::HAS_MANY,
                        Relation::TARGET => static::PAGE_ROLE,
                        Relation::LOAD => Relation::LOAD_EAGER,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::NULLABLE => true,
                            Relation::INNER_KEY => 'block_id',
                            Relation::OUTER_KEY => 'block_id',
                        ],
                    ],
                ],
            ],
            static::PAGE_ROLE => [
                SchemaInterface::ENTITY => Page::class,
                SchemaInterface::MAPPER => static::DEFAULT_MAPPER,
                SchemaInterface::DATABASE => 'default',
                SchemaInterface::TABLE => 'page_table',
                SchemaInterface::PRIMARY_KEY => 'id',
                SchemaInterface::COLUMNS => ['id', 'title', 'content', 'block_id', 'owner_id'],
                SchemaInterface::TYPECAST => ['id' => 'int', 'block_id' => 'int', 'owner_id' => 'int'],
                SchemaInterface::RELATIONS => [
                    'owner' => [
                        Relation::TYPE => Relation::BELONGS_TO,
                        Relation::TARGET => static::ENGINEER_ROLE,
                        Relation::LOAD => Relation::LOAD_PROMISE,
                        Relation::SCHEMA => [
                            Relation::NULLABLE => true,
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => 'owner_id',
                            Relation::OUTER_KEY => 'id',
                        ],
                    ],
                    'ebook' => [
                        Relation::TYPE => Relation::BELONGS_TO,
                        Relation::TARGET => static::EBOOK_ROLE,
                        Relation::LOAD => Relation::LOAD_PROMISE,
                        Relation::SCHEMA => [
                            Relation::NULLABLE => true,
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => 'block_id',
                            Relation::OUTER_KEY => 'block_id',
                        ],
                    ],
                ],
            ],
            static::HTML_PAGE_ROLE => [
                SchemaInterface::ENTITY => HtmlPage::class,
                SchemaInterface::MAPPER => static::DEFAULT_MAPPER,
                SchemaInterface::DATABASE => 'default',
                SchemaInterface::TABLE => 'html_page_table',
                SchemaInterface::PARENT => static::PAGE_ROLE,
                SchemaInterface::PRIMARY_KEY => 'id',
                SchemaInterface::COLUMNS => ['id'],
                SchemaInterface::TYPECAST => ['id' => 'int'],
            ],
            static::MARKDOWN_PAGE_ROLE => [
                SchemaInterface::ENTITY => MarkdownPage::class,
                SchemaInterface::MAPPER => static::DEFAULT_MAPPER,
                SchemaInterface::DATABASE => 'default',
                SchemaInterface::TABLE => 'markdown_page_table',
                SchemaInterface::PARENT => static::PAGE_ROLE,
                SchemaInterface::PRIMARY_KEY => 'id',
                SchemaInterface::COLUMNS => ['id'],
                SchemaInterface::TYPECAST => ['id' => 'int'],
            ],
            static::TOOL_ROLE => [
                SchemaInterface::ENTITY => Tool::class,
                SchemaInterface::MAPPER => static::DEFAULT_MAPPER,
                SchemaInterface::DATABASE => 'default',
                SchemaInterface::TABLE => 'tool_table',
                SchemaInterface::PRIMARY_KEY => 'id',
                SchemaInterface::COLUMNS => ['id', 'title', 'engineer_id'],
                SchemaInterface::TYPECAST => ['id' => 'int', 'engineer_id' => 'int'],
            ],
        ];
    }

    /**
     * Subclasses in relations should be loaded and initialized
     */
    public function testLoadSubclassAndCheckParentsRelations(): void
    {
        /** @var Programator $entity */
        $entity = (new Select($this->orm, static::PROGRAMATOR_ROLE))
            ->load('tech_book')
            ->wherePK(2)->fetchOne();

        $this->assertInstanceof(Programator::class, $entity);
        /** BOOK_3 */
        $this->assertInstanceof(Book::class, $entity->tech_book);
        $this->assertSame(3, $entity->tech_book->id);
        /** EBOOK_3 */
        $this->assertInstanceof(EBook::class, $entity->tech_book);
        $this->assertSame(static::EBOOK_3['url'], $entity->tech_book->url);
        // Check relation eager loading in the book subclass
        /** @var EBook $ebook */
        $ebook = $entity->tech_book;
        $this->assertCount(3, $ebook->pages);
    }

    /**
     * Subclass relations can't be resolved manually
     */
    public function testLoadRelationOfSubclass(): void
    {
        $this->expectException(LoaderException::class);
        $this->expectExceptionMessage('Unable to create loader: Undefined relation `human`.`book`.');

        /** @var Programator $entity */
        (new Select($this->orm, static::HUMAN_ROLE))
            // Load subclass relation
            ->load('book')
            ->wherePK(2)->fetchOne();
    }

    public function testLoadBaseClassAndCheckSubclassEagerRelations(): void
    {
        /** @var Programator $entity */
        $entity = (new Select($this->orm, static::HUMAN_ROLE))
            ->wherePK(2)->fetchOne();

        $this->assertInstanceof(Programator::class, $entity);
        /** BOOK_4 */
        $this->assertInstanceof(Book::class, $entity->book);
        $this->assertSame(4, $entity->book->id);
        /** EBOOK_4 */
        $this->assertInstanceof(EBook::class, $entity->book);
        $this->assertSame(static::EBOOK_4['url'], $entity->book->url);
        // Check relation eager loading in the book subclass
        /** @var EBook $ebook */
        $ebook = $entity->book;
        $this->assertEmpty($ebook->pages);
    }

    public function testInnerLoadParentClassRelation(): void
    {
        $this->captureReadQueries();

        /** @var MarkdownPage $entity */
        $entity = (new Select($this->orm, static::MARKDOWN_PAGE_ROLE))
            ->load('owner', ['method' => Select::SINGLE_QUERY])
            ->wherePK(1)->fetchOne();

        $this->assertNumReads(2);

        $this->assertInstanceof(MarkdownPage::class, $entity);
        $this->assertInstanceof(Programator::class, $entity->owner);
    }

    /**
     * Inheritance hierarchy should be joined as sub-query
     */
    public function testInnerLoadParentClassRelationNullToNull(): void
    {
        /** @var MarkdownPage $entity */
        $entity = (new Select($this->orm, static::MARKDOWN_PAGE_ROLE))
            ->load('ebook', ['method' => Select::SINGLE_QUERY])
            ->wherePK(5)->fetchOne();

        $this->assertInstanceof(MarkdownPage::class, $entity);
        $this->assertNull($entity->block_id);
        $this->assertNull($entity->ebook);
    }

    public function testLoadTwoInheritancesInSingleQuery(): void
    {
        $this->captureReadQueries();

        /** @var Programator $entity */
        $entity = (new Select($this->orm, static::PROGRAMATOR_ROLE))
            ->load('book', ['method' => Select::SINGLE_QUERY])
            ->load('tech_book', ['method' => Select::SINGLE_QUERY])
            ->load('tech_book.pages', ['method' => Select::SINGLE_QUERY])
            ->wherePK(2)->fetchOne();

        $this->assertNumReads(1);
        $this->captureReadQueries();

        $this->assertInstanceof(EBook::class, $entity->book);
        $this->assertSame(4, $entity->book->id);
        $this->assertInstanceof(EBook::class, $entity->tech_book);
        $this->assertSame(3, $entity->tech_book->id);
        $this->assertNotEmpty($entity->tech_book->pages);

        // Don't use promises
        $this->assertNumReads(0);
    }

    public function testPersistRelatedHierarchy(): void
    {
        $entity = $this->orm->make(EBook::class, ['title' => 'awesome book', 'url' => 'awesome-book.com']);
        $entity->pages = [
            $this->orm->make(Page::class, ['title' => 'page 1', 'content' => '...', 'owner_id' => 1]),
            $this->orm->make(Page::class, ['title' => 'page 2', 'content' => '...', 'owner_id' => 1]),
            $this->orm->make(MarkdownPage::class, ['title' => 'page 3', 'content' => '...', 'owner_id' => 2]),
        ];

        $this->captureWriteQueries();
        $this->save($entity);
        $this->assertNumWrites(6);

        $this->captureWriteQueries();
        $this->save($entity);
        $this->assertNumWrites(0);
    }
}
