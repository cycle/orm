<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Treap\Tests;

use PHPUnit\Framework\TestCase;
use Spiral\Treap\Parser\RootNode;
use Spiral\Treap\Parser\SingularNode;

class ParserTest extends TestCase
{
    public function testRoot()
    {
        $node = new RootNode(['id', 'email'], 'id');

        $data = [
            [1, 'email@gmail.com'],
            [2, 'other@gmail.com']
        ];

        foreach ($data as $row) {
            $node->parseRow(0, $row);
        }

        $this->assertSame([
            [
                'id'    => 1,
                'email' => 'email@gmail.com'
            ],
            [
                'id'    => 2,
                'email' => 'other@gmail.com'
            ]
        ], $node->getResult());
    }

    public function testRootDuplicate()
    {
        $node = new RootNode(['id', 'email'], 'id');

        $data = [
            [1, 'email@gmail.com'],
            [2, 'other@gmail.com'],
            [1, 'other@gmail.com'],
            [2, 'other@gmail.com'],
        ];

        foreach ($data as $row) {
            $node->parseRow(0, $row);
        }

        $this->assertSame([
            [
                'id'    => 1,
                'email' => 'email@gmail.com'
            ],
            [
                'id'    => 2,
                'email' => 'other@gmail.com'
            ]
        ], $node->getResult());
    }

    public function testSingular()
    {
        $node = new RootNode(['id', 'email'], 'id');
        $node->joinNode('balance', new SingularNode(
            ['id', 'user_id', 'balance'],
            'id',
            'user_id',
            'id'
        ));

        $data = [
            [1, 'email@gmail.com', 1, 1, 100],
            [2, 'other@gmail.com', 2, 2, 200],
            [3, 'third@gmail.com', null, null, null],
        ];

        foreach ($data as $row) {
            $node->parseRow(0, $row);
        }

        $this->assertSame([
            [
                'id'      => 1,
                'email'   => 'email@gmail.com',
                'balance' => [
                    'id'      => 1,
                    'user_id' => 1,
                    'balance' => 100
                ]
            ],
            [
                'id'      => 2,
                'email'   => 'other@gmail.com',
                'balance' => [
                    'id'      => 2,
                    'user_id' => 2,
                    'balance' => 200
                ]
            ],
            [
                'id'      => 3,
                'email'   => 'third@gmail.com',
                'balance' => null
            ],
        ], $node->getResult());
    }

    public function testGetReferences()
    {
        $node = new RootNode(['id', 'email'], 'id');
        $node->linkNode('balance', $child = new SingularNode(
            ['id', 'user_id', 'balance'],
            'id',
            'user_id',
            'id'
        ));

        $data = [
            [1, 'email@gmail.com'],
            [2, 'other@gmail.com'],
            [3, 'third@gmail.com'],
        ];

        foreach ($data as $row) {
            $node->parseRow(0, $row);
        }

        $this->assertSame([1, 2, 3], $child->getReferences());
    }

    /**
     * @expectedException \Spiral\Treap\Exception\NodeException
     */
    public function testGetReferencesWithoutParent()
    {
        $child = new SingularNode(
            ['id', 'user_id', 'balance'],
            'id',
            'user_id',
            'id'
        );

        $child->getReferences();
    }

    public function testSingularOverExternal()
    {
        $node = new RootNode(['id', 'email'], 'id');
        $node->linkNode('balance', $child = new SingularNode(
            ['id', 'user_id', 'balance'],
            'id',
            'user_id',
            'id'
        ));

        $data = [
            [1, 'email@gmail.com'],
            [2, 'other@gmail.com'],
            [3, 'third@gmail.com'],
        ];

        foreach ($data as $row) {
            $node->parseRow(0, $row);
        }

        $childData = [
            [1, 1, 100],
            [2, 2, 200]
        ];

        foreach ($childData as $row) {
            $child->parseRow(0, $row);
        }

        $this->assertSame([
            [
                'id'      => 1,
                'email'   => 'email@gmail.com',
                'balance' => [
                    'id'      => 1,
                    'user_id' => 1,
                    'balance' => 100
                ]
            ],
            [
                'id'      => 2,
                'email'   => 'other@gmail.com',
                'balance' => [
                    'id'      => 2,
                    'user_id' => 2,
                    'balance' => 200
                ]
            ],
            [
                'id'      => 3,
                'email'   => 'third@gmail.com',
                'balance' => null
            ],
        ], $node->getResult());
    }

    /**
     * @expectedException \Spiral\Treap\Exception\NodeException
     */
    public function testInvalidColumnCount()
    {
        $node = new RootNode(['id', 'email'], 'id');
        $node->joinNode('balance', new SingularNode(
            ['id', 'user_id', 'balance'],
            'id',
            'user_id',
            'id'
        ));

        $data = [
            [1, 'email@gmail.com', 1, 1, 100],
            [2, 'other@gmail.com', 2, 2],
            [3, 'third@gmail.com', null, null, null],
        ];

        foreach ($data as $row) {
            $node->parseRow(0, $row);
        }
    }

    public function testGetNode()
    {
        $node = new RootNode(['id', 'email'], 'id');
        $node->joinNode('balance', $child = new SingularNode(
            ['id', 'user_id', 'balance'],
            'id',
            'user_id',
            'id'
        ));

        $this->assertInstanceOf(SingularNode::class, $node->getNode('balance'));
    }

    /**
     * @expectedException \Spiral\Treap\Exception\NodeException
     */
    public function testGetUndefinedNode()
    {
        $node = new RootNode(['id', 'email'], 'id');
        $node->getNode('balance');
    }

    /**
     * @expectedException \Spiral\Treap\Exception\NodeException
     */
    public function testSingularParseWithoutParent()
    {
        $node = new SingularNode(
            ['id', 'user_id', 'balance'],
            'id',
            'user_id',
            'id'
        );

        $node->parseRow(0, []);
    }
}