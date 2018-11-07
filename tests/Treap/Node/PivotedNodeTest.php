<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Treap\Tests\Node;

use PHPUnit\Framework\TestCase;
use Spiral\Treap\Node\PivotedNode;
use Spiral\Treap\Node\PivotedRootNode;
use Spiral\Treap\Node\RootNode;

class PivotedNodeTest extends TestCase
{
    public function testRoot()
    {
        $node = new PivotedRootNode(
            ['id', 'email'],
            ['user_id', 'rule_id'],
            'id',
            'user_id',
            'rule_id'
        );

        $data = [
            [1, 2, 1, 'email@gmail.com'],
            [2, 2, 2, 'other@gmail.com'],
            [2, 3, 2, 'other@gmail.com'],
        ];

        foreach ($data as $row) {
            $node->parseRow(0, $row);
        }

        $this->assertSame([
            [
                '@pivot' => [
                    'user_id' => 1,
                    'rule_id' => 2
                ],
                'id'     => 1,
                'email'  => 'email@gmail.com'
            ],
            [
                '@pivot' => [
                    'user_id' => 2,
                    'rule_id' => 2
                ],
                'id'     => 2,
                'email'  => 'other@gmail.com'
            ],
            [
                '@pivot' => [
                    'user_id' => 2,
                    'rule_id' => 3
                ],
                'id'     => 2,
                'email'  => 'other@gmail.com'
            ]
        ], $node->getResult());
    }

    public function testNested()
    {
        $node = new RootNode(['id', 'email'], 'id');
        $node->joinNode('roles', new PivotedNode(
            ['id', 'name'],
            ['user_id', 'role_id', 'added'],
            'id',
            'user_id',
            'role_id'
        ));

        $data = [
            [1, 'email@gmail.com', 1, 1, 'yesterday', 1, 'admin'],
            [2, 'other@gmail.com', 2, 1, 'today', 1, 'admin'],
            [2, 'other@gmail.com', 2, 2, 'today', 2, 'moderator'],
            [2, 'other@gmail.com', 2, 3, 'last-week', 3, 'super-admin'],
            [3, 'third@gmail.com', null, null, null, null, null],
            [3, 'third@gmail.com', null, null, null, null, null],
        ];

        foreach ($data as $row) {
            $node->parseRow(0, $row);
        }

        $this->assertSame([
            [
                'id'    => 1,
                'email' => 'email@gmail.com',
                'roles' => [
                    [
                        '@pivot' => [
                            'user_id' => 1,
                            'role_id' => 1,
                            'added'   => 'yesterday',
                        ],
                        'id'     => 1,
                        'name'   => 'admin',
                    ],
                ],
            ],
            [
                'id'    => 2,
                'email' => 'other@gmail.com',
                'roles' => [
                    [
                        '@pivot' => [
                            'user_id' => 2,
                            'role_id' => 1,
                            'added'   => 'today',
                        ],
                        'id'     => 1,
                        'name'   => 'admin',
                    ],
                    [
                        '@pivot' => [
                            'user_id' => 2,
                            'role_id' => 2,
                            'added'   => 'today',
                        ],
                        'id'     => 2,
                        'name'   => 'moderator',
                    ],
                    [
                        '@pivot' => [
                            'user_id' => 2,
                            'role_id' => 3,
                            'added'   => 'last-week',
                        ],
                        'id'     => 3,
                        'name'   => 'super-admin',
                    ],
                ],
            ],
            [
                'id'    => 3,
                'email' => 'third@gmail.com',
                'roles' => [],
            ],
        ], $node->getResult());
    }
}