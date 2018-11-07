<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Treap\Tests\Treap;

use PHPUnit\Framework\TestCase;
use Spiral\Treap\Node\PivotedRootNode;

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
}