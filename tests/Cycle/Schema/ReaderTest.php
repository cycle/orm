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
use Spiral\Annotations\Parser;
use Spiral\Annotations\Reader;
use Spiral\Cycle\Schema\Annotated\Annotation\Column;
use Spiral\Cycle\Schema\Annotated\Annotation\Entity;
use Spiral\Cycle\Tests\Fixtures\Annotated;

class ReaderTest extends TestCase
{
    public function testEntityAnnotation()
    {
        $reader = new Reader(new Parser(), [new Entity()]);
        $ann = $reader->classAnnotations(new \ReflectionClass(Annotated::class));
        $this->assertCount(1, $ann);
        $this->assertInstanceOf(Entity::class, $ann['entity']);
    }

    public function testPropertyAnnotation()
    {
        $reader = new Reader(new Parser(), [], [], [new Column()]);

        $ann = $reader->propertyAnnotations(new \ReflectionClass(Annotated::class), 'id');
        $this->assertCount(1, $ann);
        $this->assertInstanceOf(Column::class, $ann['column']);
        $this->assertSame('primary', $ann['column']->getType());
        $this->assertSame('internal_id', $ann['column']->getInternalName());
    }
}