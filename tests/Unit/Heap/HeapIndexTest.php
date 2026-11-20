<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Heap;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Heap\Node;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(Heap::class)]
final class HeapIndexTest
{
    public function scopeWithExtraFieldsUsesTheIndexItCovers(): void
    {
        $heap = new Heap();
        $entity = new \stdClass();
        $heap->attach($entity, new Node(Node::MANAGED, ['id' => 1, 'email' => 'a@b'], 'user'), ['email', 'id']);

        Assert::same($heap->find('user', ['id' => 1, 'name' => 'foo']), $entity);
        Assert::null($heap->find('user', ['id' => 2, 'name' => 'foo']));
        Assert::null($heap->find('user', ['name' => 'foo', 'login' => 'bar']));
    }

    public function scopeIsResolvedByTheFirstCoveredIndex(): void
    {
        $heap = new Heap();
        $first = new \stdClass();
        $second = new \stdClass();
        $heap->attach($first, new Node(Node::MANAGED, ['id' => 1, 'email' => 'first@b'], 'user'), ['id', 'email']);
        $heap->attach($second, new Node(Node::MANAGED, ['id' => 2, 'email' => 'second@b'], 'user'), ['id', 'email']);

        Assert::same($heap->find('user', ['id' => 1, 'email' => 'second@b', 'x' => 0]), $first);
    }

    public function detachErasesIndexesOfTheCurrentState(): void
    {
        $heap = new Heap();
        $entity = new \stdClass();
        $node = new Node(Node::MANAGED, ['id' => 1, 'email' => 'old@b'], 'user');
        $node->createState()->register('email', 'new@b');
        $heap->attach($entity, $node, ['email']);

        $heap->detach($entity);

        Assert::null($heap->find('user', ['email' => 'new@b']));
        Assert::null($heap->find('user', ['email' => 'old@b']));
    }

    public function detachErasesEveryIndexAfterAnEmptyOne(): void
    {
        $heap = new Heap();
        $first = new \stdClass();
        $second = new \stdClass();
        $heap->attach($first, new Node(Node::MANAGED, ['id' => 1], 'user'), ['id']);
        $heap->attach($second, new Node(Node::MANAGED, ['id' => 2, 'email' => 'a@b'], 'user'), ['email']);
        $heap->detach($first);

        $heap->detach($second);

        Assert::null($heap->find('user', ['email' => 'a@b']));
    }

    public function cloneHasItsOwnStorage(): void
    {
        $heap = new Heap();
        $clone = clone $heap;
        $entity = new \stdClass();

        $clone->attach($entity, new Node(Node::MANAGED, ['id' => 1], 'user'));

        Assert::true($clone->has($entity));
        Assert::false($heap->has($entity));
    }
}
