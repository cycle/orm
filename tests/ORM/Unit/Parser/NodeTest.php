<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Parser\Unit;

use Cycle\ORM\Exception\ParserException;
use Cycle\ORM\Parser\ArrayNode;
use Cycle\ORM\Parser\RootNode;
use Cycle\ORM\Parser\SingularNode;
use PHPUnit\Framework\TestCase;

class NodeTest
{
    #[\Testo\Test]
    public function testRoot(): void
    {
        $node = new RootNode(['id', 'email'], ['id']);

        $data = [
            [1, 'email@gmail.com'],
            [2, 'other@gmail.com'],
        ];

        foreach ($data as $row) {
            $node->parseRow(0, $row);
        }

        \Testo\Assert::same($node->getResult(), [
            [
                'id' => 1,
                'email' => 'email@gmail.com',
            ],
            [
                'id' => 2,
                'email' => 'other@gmail.com',
            ],
        ]);
    }

    #[\Testo\Test]
    public function testRootDuplicate(): void
    {
        $node = new RootNode(['id', 'email'], ['id']);

        $data = [
            [1, 'email@gmail.com'],
            [2, 'other@gmail.com'],
            [1, 'other@gmail.com'],
            [2, 'other@gmail.com'],
        ];

        foreach ($data as $row) {
            $node->parseRow(0, $row);
        }

        \Testo\Assert::same($node->getResult(), [
            [
                'id' => 1,
                'email' => 'email@gmail.com',
            ],
            [
                'id' => 2,
                'email' => 'other@gmail.com',
            ],
        ]);
    }

    #[\Testo\Test]
    public function testSingular(): void
    {
        $node = new RootNode(['id', 'email'], ['id']);
        $node->joinNode('balance', $this->createSingularNode());

        $data = [
            [1, 'email@gmail.com', 1, 1, 100],
            [2, 'other@gmail.com', 2, 2, 200],
            [3, 'third@gmail.com', null, null, null],
        ];

        foreach ($data as $row) {
            $node->parseRow(0, $row);
        }

        \Testo\Assert::same($node->getResult(), [
            [
                'id' => 1,
                'email' => 'email@gmail.com',
                'balance' => [
                    'id' => 1,
                    'user_id' => 1,
                    'balance' => 100,
                ],
            ],
            [
                'id' => 2,
                'email' => 'other@gmail.com',
                'balance' => [
                    'id' => 2,
                    'user_id' => 2,
                    'balance' => 200,
                ],
            ],
            [
                'id' => 3,
                'email' => 'third@gmail.com',
                'balance' => null,
            ],
        ]);
    }

    #[\Testo\Test]
    public function testGetReferences(): void
    {
        $node = new RootNode(['id', 'email'], ['id']);
        $node->linkNode('balance', $child = $this->createSingularNode());

        $data = [
            [1, 'email@gmail.com'],
            [2, 'other@gmail.com'],
            [3, 'third@gmail.com'],
        ];

        foreach ($data as $row) {
            $node->parseRow(0, $row);
        }

        \Testo\Assert::same($child->getReferenceValues(), [['id' => 1], ['id' => 2], ['id' => 3]]);
    }

    #[\Testo\Test]
    public function testGetReferencesWithoutParent(): void
    {
        \Testo\Expect::exception(ParserException::class);

        $child = $this->createSingularNode();

        $child->getReferenceValues();
    }

    #[\Testo\Test]
    public function testSingularOverExternal(): void
    {
        $node = new RootNode(['id', 'email'], ['id']);
        $node->linkNode('balance', $child = $this->createSingularNode());

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
            [2, 2, 200],
        ];

        foreach ($childData as $row) {
            $child->parseRow(0, $row);
        }

        \Testo\Assert::same($node->getResult(), [
            [
                'id' => 1,
                'email' => 'email@gmail.com',
                'balance' => [
                    'id' => 1,
                    'user_id' => 1,
                    'balance' => 100,
                ],
            ],
            [
                'id' => 2,
                'email' => 'other@gmail.com',
                'balance' => [
                    'id' => 2,
                    'user_id' => 2,
                    'balance' => 200,
                ],
            ],
            [
                'id' => 3,
                'email' => 'third@gmail.com',
                'balance' => null,
            ],
        ]);
    }

    #[\Testo\Test]
    public function testSingularInvalidReference(): void
    {
        \Testo\Expect::exception(ParserException::class);

        $node = new RootNode(['id', 'email'], ['id']);
        $node->linkNode('balance', $child = $this->createSingularNode());

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
            [2, -1, 200],
        ];

        foreach ($childData as $row) {
            $child->parseRow(0, $row);
        }
    }

    #[\Testo\Test]
    public function testInvalidColumnCount(): void
    {
        \Testo\Expect::exception(ParserException::class);

        $node = new RootNode(['id', 'email'], ['id']);
        $node->joinNode('balance', $this->createSingularNode());

        $data = [
            [1, 'email@gmail.com', 1, 1, 100],
            [2, 'other@gmail.com', 2, 2],
            [3, 'third@gmail.com', null, null, null],
        ];

        foreach ($data as $row) {
            $node->parseRow(0, $row);
        }
    }

    #[\Testo\Test]
    public function testGetNode(): void
    {
        $node = new RootNode(['id', 'email'], ['id']);
        $node->joinNode('balance', $child = $this->createSingularNode());

        \Testo\Assert::instanceOf($node->getNode('balance'), SingularNode::class);
    }

    #[\Testo\Test]
    public function testGetUndefinedNode(): void
    {
        \Testo\Expect::exception(ParserException::class);

        $node = new RootNode(['id', 'email'], ['id']);
        $node->getNode('balance');
    }

    #[\Testo\Test]
    public function testSingularParseWithoutParent(): void
    {
        \Testo\Expect::exception(ParserException::class);

        $node = $this->createSingularNode();

        $node->parseRow(0, [1, 10, 10]);
    }

    #[\Testo\Test]
    public function testArray(): void
    {
        $node = new RootNode(['id', 'email'], ['id']);
        $node->joinNode('lines', new ArrayNode(['id', 'user_id', 'value'], ['id'], ['user_id'], ['id']));

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

        \Testo\Assert::same($node->getResult(), [
            [
                'id' => 1,
                'email' => 'email@gmail.com',
                'lines' => [
                    [
                        'id' => 1,
                        'user_id' => 1,
                        'value' => 100,
                    ],
                ],
            ],
            [
                'id' => 2,
                'email' => 'other@gmail.com',
                'lines' => [
                    [
                        'id' => 2,
                        'user_id' => 2,
                        'value' => 200,
                    ],
                    [
                        'id' => 3,
                        'user_id' => 2,
                        'value' => 300,
                    ],
                ],
            ],
            [
                'id' => 3,
                'email' => 'third@gmail.com',
                'lines' => [],
            ],
        ]);
    }

    #[\Testo\Test]
    public function testArrayInvalidReference(): void
    {
        $node = new RootNode(['id', 'email'], ['id']);
        $node->linkNode('balance', $child = new ArrayNode(['id', 'user_id', 'balance'], ['id'], ['user_id'], ['id']));

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
            [2, -1, 200],
        ];

        \Testo\Expect::exception(ParserException::class);

        foreach ($childData as $row) {
            $child->parseRow(0, $row);
        }
    }

    #[\Testo\Test]
    public function testArrayWithoutParent(): void
    {
        \Testo\Expect::exception(ParserException::class);

        $node = new ArrayNode(['id', 'user_id', 'balance'], ['id'], ['user_id'], ['id']);

        $node->parseRow(0, [1, 10, 10]);
    }

    private function createSingularNode(): SingularNode
    {
        return new SingularNode(['id', 'user_id', 'balance'], ['id'], ['user_id'], ['id']);
    }
}
