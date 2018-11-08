<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Treap\Tests\Treap;

use PHPUnit\Framework\TestCase;
use Spiral\Treap\ORM;

class IntTest extends TestCase
{
    public function testORM()
    {
        $orm = new ORM();
        dump($orm->database(''));
    }
}