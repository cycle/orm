<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Schema\Annotated;

use Spiral\Cycle\Schema\Annotated\Node\Column;
use Spiral\Cycle\Schema\Annotated\Node\Entity;
use Spiral\Cycle\Schema\Annotated\Node\Index;
use Spiral\Cycle\Schema\Annotated\Node\Table;

class AnnotatedEntity
{
    /** @var string */
    private $class;

    /** @var string */
    private $role;

    /**
     * @param string      $class
     * @param string|null $role
     * @throws \ReflectionException
     */
    public function __construct(string $class, string $role = null)
    {
        $this->class = $class;
        $this->role = $role;

        $parser = new Parser();
        $parser->register([
            'entity' => new Entity(),
            'table'  => new Table(),
            'index'  => new Index(),
            'column' => new Column()
        ]);

        dump($parser->parse((new \ReflectionClass($class))->getDocComment()));
    }

    /**
     * @return string
     */
    public function getRole(): string
    {
        return $this->role;
    }
}