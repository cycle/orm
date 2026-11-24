<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Relation;

use Cycle\ORM\Mapper\StdMapper;
use Cycle\ORM\Reference\EmptyReference;
use Cycle\ORM\Reference\ReferenceInterface;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\Testing\ORM\OrmTestCase;
use Testo\Assert;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Test]
final class ToOneReferenceScopeTest extends OrmTestCase
{
    public function nullKeyGivesResolvedEmptyReference(): void
    {
        $comment = $this->orm->get('comment', ['id' => 1]);

        $author = $this->author($comment);

        Assert::true($author->hasValue());
        Assert::null($author->getValue());
    }

    public function missingKeyGivesEmptyReference(): void
    {
        $comment = $this->orm->make('comment', ['message' => 'new']);

        Assert::instanceOf($this->author($comment), EmptyReference::class);
    }

    #[BeforeTest]
    public function setUp(): void
    {
        $this->makeTable('user', ['id' => 'primary', 'email' => 'string']);
        $this->makeTable('comment', ['id' => 'primary', 'user_id' => 'integer,nullable', 'message' => 'string']);
        $this->getDatabase()->table('comment')->insertMultiple(['user_id', 'message'], [[null, 'orphan']]);

        $this->orm = $this->withSchema(new Schema([
            'user' => [
                Schema::MAPPER => StdMapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'email'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [],
            ],
            'comment' => [
                Schema::MAPPER => StdMapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'comment',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'user_id', 'message'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [
                    'author' => [
                        Relation::TYPE => Relation::BELONGS_TO,
                        Relation::TARGET => 'user',
                        Relation::LOAD => Relation::LOAD_PROMISE,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::NULLABLE => true,
                            Relation::INNER_KEY => 'user_id',
                            Relation::OUTER_KEY => 'id',
                        ],
                    ],
                ],
            ],
        ]));
    }

    private function author(object $comment): ReferenceInterface
    {
        return $this->orm->getRelationMap('comment')->getRelations()['author']
            ->initReference($this->orm->getHeap()->get($comment));
    }
}
