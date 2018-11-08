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
use Spiral\Treap\Schema;

class IntTest extends TestCase
{
    public function testInt()
    {
        $orm = new ORM();

        $s = new Schema();
        $s->database = 'default';
        $s->table = 'users';
        $orm->setSchema(User::class, $s);

    }
}

class User
{

}