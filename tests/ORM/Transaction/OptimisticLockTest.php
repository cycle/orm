<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Transaction;

use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Tests\BaseTest;
use Cycle\ORM\Tests\Fixtures\OptimisticLockMapper;
use Cycle\ORM\Tests\Fixtures\Post;
use Cycle\ORM\Tests\Traits\TableTrait;
use Cycle\ORM\Transaction;

abstract class OptimisticLockTest extends BaseTest
{
    use TableTrait;

    public function setUp(): void
    {
        parent::setUp();

        $this->makeTable('post', [
            'id' => 'primary',
            'title' => 'string',
            'content' => 'string',
            'lock' => 'string',
        ]);

        $this->orm = $this->withSchema(new Schema([
            Post::class => [
                SchemaInterface::ROLE => 'post',
                SchemaInterface::MAPPER => OptimisticLockMapper::class,
                SchemaInterface::DATABASE => 'default',
                SchemaInterface::TABLE => 'post',
                SchemaInterface::PRIMARY_KEY => 'id',
                SchemaInterface::COLUMNS => ['id', 'title', 'content', 'lock'],
                SchemaInterface::SCHEMA => [],
                SchemaInterface::RELATIONS => [],
            ],
        ]));
    }

    /**
     * Check the mapper works fine in a normal create and update cases
     */
    public function testCreateAndUpdate(): void
    {
        $entity = new Post();
        $entity->title = 'Test title';
        $entity->content = 'Test content';
        $entity->lock = microtime();

        $this->captureWriteQueries();
        $this->save($entity);
        $this->assertNumWrites(1);

        $oldLock = $entity->lock;
        $entity->title = 'new title';
        $this->captureWriteQueries();
        $this->save($entity);
        $this->assertNumWrites(1);

        $this->assertNotSame($oldLock, $entity->lock);
    }

    public function testUpdateLocked(): void
    {
        $entity = new Post();
        $entity->title = 'Test title';
        $entity->content = 'Test content';
        $entity->lock = microtime();
        $this->save($entity);

        $this->getDatabase()->update('post', ['lock' => microtime()], ['id' => $entity->id])->run();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The `post` record is locked.');

        try {
            $entity->title = 'new title';
            $this->save($entity);
        } finally {
            $this->orm->getHeap()->clean();
        }
    }

    /**
     * Check the mapper works fine in a normal delete case
     */
    public function testCreateAndDelete(): void
    {
        $entity = new Post();
        $entity->title = 'Test title';
        $entity->content = 'Test content';
        $entity->lock = microtime();
        $this->save($entity);

        $this->captureWriteQueries();
        (new Transaction($this->orm))->delete($entity)->run();
        $this->assertNumWrites(1);
    }

    public function testCreateAndDeleteLocked(): void
    {
        $entity = new Post();
        $entity->title = 'Test title';
        $entity->content = 'Test content';
        $entity->lock = microtime();
        $this->save($entity);

        $this->getDatabase()->update('post', ['lock' => microtime()], ['id' => $entity->id])->run();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The `post` record is locked.');

        try {
            (new Transaction($this->orm))->delete($entity)->run();
        } finally {
            $this->orm->getHeap()->clean();
        }
    }
}
