<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Treap\Relation;

final class RelationMap
{
//    /**
//     * @var array|RelationInterface[]
//     */
//    private $relations = [];
//
//    /**
//     * Parent class name.
//     *
//     * @var string
//     */
//    private $class;
//
//    /**
//     * Parent model.
//     *
//     * @var RecordInterface
//     */
//    private $parent;
//
//    /**
//     * Relations schema.
//     *
//     * @var array
//     */
//    private $schema = [];
//
//    /**
//     * Associates ORM manager.
//     *
//     * @var ORMInterface
//     */
//    protected $orm;
//
//    /**
//     * @param RecordInterface $record
//     * @param ORMInterface    $orm
//     */
//    public function __construct(RecordInterface $record, ORMInterface $orm)
//    {
//        $this->class = get_class($record);
//        $this->parent = $record;
//        $this->schema = (array)$orm->define($this->class, ORMInterface::R_RELATIONS);
//        $this->orm = $orm;
//    }
//
//    /**
//     * Extract relations data from given entity fields.
//     *
//     * @param array $data
//     */
//    public function extractRelations(array &$data)
//    {
//        //Fetch all relations
//        $relations = array_intersect_key($data, $this->schema);
//
//        foreach ($relations as $name => $relation) {
//            $this->relations[$name] = $relation;
//            unset($data[$name]);
//        }
//    }
//
//    /**
//     * Generate command tree with or without relation to parent command in order to specify update
//     * or insert sequence. Commands might define dependencies between each other in order to extend
//     * FK values.
//     *
//     * @param ContextualCommandInterface $parent
//     *
//     * @return ContextualCommandInterface
//     */
//    public function queueRelations(ContextualCommandInterface $parent): ContextualCommandInterface
//    {
//        if (empty($this->relations)) {
//            //No relations exists, nothing to do
//            return $parent;
//        }
//
//        //We have to execute multiple commands at once
//        $transaction = new TransactionalCommand();
//
//        foreach ($this->leadingRelations() as $relation) {
//            //Generating commands needed to save given relation prior to parent command
//            if ($relation->isLoaded()) {
//                $transaction->addCommand($relation->queueCommands($parent));
//            }
//        }
//
//        //Parent model save operations (true state that this is leading/primary command)
//        $transaction->addCommand($parent, true);
//
//        foreach ($this->dependedRelations() as $relation) {
//            //Generating commands needed to save relations after parent command being executed
//            if ($relation->isLoaded()) {
//                $transaction->addCommand($relation->queueCommands($parent));
//            }
//        }
//
//        return $transaction;
//    }
//
//    /**
//     * Check if parent entity has associated relation.
//     *
//     * @param string $relation
//     *
//     * @return bool
//     */
//    public function has(string $relation): bool
//    {
//        return isset($this->schema[$relation]);
//    }
//
//    /**
//     * Check if relation has any associated data with it (attention, non loaded relation will be
//     * automatically pre-loaded).
//     *
//     * @param string $relation
//     *
//     * @return bool
//     */
//    public function hasRelated(string $relation): bool
//    {
//        //Checking with only loaded records
//        return $this->get($relation)->hasRelated();
//    }
//
//    /**
//     * Data data which is being associated with relation, relation is allowed to return itself if
//     * needed.
//     *
//     * @param string $relation
//     *
//     * @return RelationInterface|RecordInterface|mixed
//     *
//     * @throws RelationException
//     */
//    public function getRelated(string $relation)
//    {
//        return $this->get($relation)->getRelated();
//    }
//
//    /**
//     * Associated relation with new value (must be compatible with relation format).
//     *
//     * @param string $relation
//     * @param mixed  $value
//     *
//     * @throws RelationException
//     */
//    public function setRelated(string $relation, $value)
//    {
//        $this->get($relation)->setRelated($value);
//    }
//
//    /**
//     * Get associated relation instance.
//     *
//     * @param string $relation
//     *
//     * @return RelationInterface
//     */
//    public function get(string $relation): RelationInterface
//    {
//        if (isset($this->relations[$relation]) && $this->relations[$relation] instanceof RelationInterface) {
//            return $this->relations[$relation];
//        }
//
//        $instance = $this->orm->makeRelation($this->class, $relation);
//        if (array_key_exists($relation, $this->relations)) {
//            //Indicating that relation is loaded
//            $instance = $instance->withContext($this->parent, true, $this->relations[$relation]);
//        } else {
//            //Not loaded relation
//            $instance = $instance->withContext($this->parent, false);
//        }
//
//        return $this->relations[$relation] = $instance;
//    }
//
//    /**
//     * Information about loaded relations.
//     *
//     * @return array
//     */
//    public function __debugInfo()
//    {
//        $relations = [];
//
//        foreach ($this->schema as $relation => $schema) {
//            $accessor = $this->get($relation);
//
//            $type = (new \ReflectionClass($accessor))->getShortName();
//            $class = (new \ReflectionClass($accessor->getClass()))->getShortName();
//
//            //[+] for loaded, [~] for lazy loaded
//            $relations[$relation] = $type . '(' . $class . ') [' . ($accessor->isLoaded() ? '+]' : '~]');
//        }
//
//        return $relations;
//    }
//
//    /**
//     * list of relations which lead data of parent record (BELONGS_TO).
//     *
//     * Example:
//     *
//     * $post = new Post();
//     * $post->user = new User();
//     *
//     * @return RelationInterface[]|\Generator
//     */
//    protected function leadingRelations()
//    {
//        foreach ($this->relations as $relation) {
//            if ($relation instanceof RelationInterface && $relation->isLeading()) {
//                yield $relation;
//            }
//        }
//    }
//
//    /**
//     * list of loaded relations which depend on parent record (HAS_MANY, MANY_TO_MANY and etc).
//     *
//     * Example:
//     *
//     * $post = new Post();
//     * $post->comments->add(new Comment());
//     *
//     * @return RelationInterface[]|\Generator
//     */
//    protected function dependedRelations()
//    {
//        foreach ($this->relations as $relation) {
//            if ($relation instanceof RelationInterface && !$relation->isLeading()) {
//                yield $relation;
//            }
//        }
//    }
}