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
use Spiral\Cycle\Select\Traits\WhereTrait;
use Spiral\Database\Query\SelectQuery;

/**
 * Loads given entity table without any specific condition.
 */
class PivotLoader extends JoinableLoader
{
    use WhereTrait;

    /**
     * Default set of relation options. Child implementation might defined their of default options.
     *
     * @var array
     */
    protected $options = [
        'constrain' => true,
        'method'    => self::JOIN,
        'minify'    => true,
        'as'        => null,
        'using'     => null
    ];

    /**
     * @inheritdoc
     */
    public function __construct(ORMInterface $orm, string $name, string $target, array $schema)
    {
        parent::__construct($orm, $name, $target, $schema);
        $this->options['constrain'] = $schema[Relation::THOUGHT_CONSTRAIN] ?? true;
    }

    /**
     * @return string
     */
    public function getTable(): string
    {
        return $this->define(Schema::TABLE);
    }

    /**
     * @inheritdoc
     */
    public function configureQuery(SelectQuery $query, array $outerKeys = []): SelectQuery
    {
        // user specified WHERE conditions
        $this->setWhere(
            $query,
            $this->getAlias(),
            $this->isJoined() ? 'onWhere' : 'where',
            $this->options['where'] ?? $this->schema[Relation::THOUGHT_WHERE] ?? []
        );

        return parent::configureQuery($query, $outerKeys);
    }

    /**
     * @inheritdoc
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