<?php
/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Cycle\ORM\Tests\Parser;

use Cycle\ORM\Parser\ArrayNode;
use Cycle\ORM\Parser\RootNode;
use Cycle\ORM\Parser\SingularNode;
use PHPUnit\Framework\TestCase;

class NodeTest extends TestCase
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
     * @expectedException \Cycle\ORM\Exception\ParserException
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
     * @expectedException \Cycle\ORM\Exception\ParserException
     */
    public function testSingularInvalidReference()
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
            [2, -1, 200]
        ];

        foreach ($childData as $row) {
            $child->parseRow(0, $row);
        }
    }

    /**
     * @expectedException \Cycle\ORM\Exception\ParserException
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
     * @expectedException \Cycle\ORM\Exception\ParserException
     */
    public function testGetUndefinedNode()
    {
        $node = new RootNode(['id', 'email'], 'id');
        $node->getNode('balance');
    }

    /**
     * @expectedException \Cycle\ORM\Exception\ParserException
     */
    public function testSingularParseWithoutParent()
    {
        $node = new SingularNode(
            ['id', 'user_id', 'balance'],
            'id',
            'user_id',
            'id'
        );

        $node->parseRow(0, [1, 10, 10]);
    }

    public function testArray()
    {
        $node = new RootNode(['id', 'email'], 'id');
        $node->joinNode('lines', new ArrayNode(
            ['id', 'user_id', 'value'],
            'id',
            'user_id',
            'id'
        ));

        $data = [
            [1, 'email@gmail.com', 1, 1, 100],
            [2, 'other@gmail.com', 2, 2, 200],
            [2, 'other@gmail.com', 3, 2, 300],
            [3, 'third@gmail.com', null, null, null],
            [3, 'third@gmail.com', null, null, null],
        ];

        foreach ($data as $row) {
            $node->parseRow(0, $row);
        }

        $this->assertSame([
            [
                'id'    => 1,
                'email' => 'email@gmail.com',
                'lines' => [
                    [
                        'id'      => 1,
                        'user_id' => 1,
                        'value'   => 100
                    ]
                ]
            ],
            [
                'id'    => 2,
                'email' => 'other@gmail.com',
                'lines' => [
                    [
                        'id'      => 2,
                        'user_id' => 2,
                        'value'   => 200
                    ],
                    [
                        'id'      => 3,
                        'user_id' => 2,
                        'value'   => 300
                    ]
                ]
            ],
            [
                'id'    => 3,
                'email' => 'third@gmail.com',
                'lines' => []
            ],
        ], $node->getResult());
    }


    /**
     * @expectedException \Cycle\ORM\Exception\ParserException
     */
    public function testArrayInvalidReference()
    {
        $node = new RootNode(['id', 'email'], 'id');
        $node->linkNode('balance', $child = new ArrayNode(
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
            [2, -1, 200]
        ];

        foreach ($childData as $row) {
            $child->parseRow(0, $row);
        }
    }

    /**
     * @expectedException \Cycle\ORM\Exception\ParserException
     */
    public function testArrayWithoutParent()
    {
        $node = new ArrayNode(
            ['id', 'user_id', 'balance'],
            'id',
            'user_id',
            'id'
        );

        $node->parseRow(0, [1, 10, 10]);
    }
}
