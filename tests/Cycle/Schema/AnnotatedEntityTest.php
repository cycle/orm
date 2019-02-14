<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Tests\Schema;

use PHPUnit\Framework\TestCase;
use Spiral\Cycle\Schema\Annotated\AnnotatedEntity;
use Spiral\Cycle\Tests\Fixtures\Annotated;

class AnnotatedEntityTest extends TestCase
{
    public function testSource()
    {
        $ann = new AnnotatedEntity(Annotated::class);


    }
}