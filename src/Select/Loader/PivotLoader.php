<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Cycle\Select\Loader;

use Spiral\Cycle\ORMInterface;
use Spiral\Cycle\Parser\AbstractNode;
use Spiral\Cycle\Parser\ArrayNode;
use Spiral\Cycle\Parser\Typecast;
use Spiral\Cycle\Relation;
use Spiral\Cycle\Schema;
use Spiral\Cycle\Select\JoinableLoader;
use Spiral\Cycle\Select\SourceInterface;

class PivotLoader extends JoinableLoader
{
    // All pivoted relations has constant name.
    public const NAME = 'pivot';

    // todo: POSITION? @pivot

    /**
     * Default set of relation options. Child implementation might defined their of default options.
     *
     * @var array
     */
    protected $options = [
        'constrain' => SourceInterface::DEFAULT_CONSTRAIN,
        'method'    => self::JOIN,
        'minify'    => true,
        'as'        => null,
        'where'     => null,
    ];

    /**
     * @param ORMInterface $orm
     * @param string       $target
     * @param array        $schema
     */
    public function __construct(ORMInterface $orm, string $target, array $schema)
    {
        parent::__construct($orm, self::NAME, $target, $schema);

        // todo: map schema
    }

    /**
     * @return string
     */
    public function getTable(): string
    {
        return $this->define(Schema::TABLE);
    }

    /**
     * @return AbstractNode
     */
    protected function initNode(): AbstractNode
    {
        $node = new ArrayNode(
            $this->columnNames(),
            $this->define(Schema::PRIMARY_KEY),
            $this->schema[Relation::THOUGHT_INNER_KEY],
            $this->schema[Relation::INNER_KEY]
        );

        $typecast = $this->define(Schema::TYPECAST);
        if ($typecast !== null) {
            $node->setTypecast(new Typecast($typecast, $this->getSource()->getDatabase()));
        }

        return $node;
    }
}