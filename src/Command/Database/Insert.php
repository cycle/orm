<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Command\Database;

use Spiral\Database\DatabaseInterface;
use Spiral\ORM\Command\CarrierInterface;
use Spiral\ORM\Command\DatabaseCommand;
use Spiral\ORM\Command\Traits\ContextTrait;

/**
 * Insert data into associated table and provide lastInsertID promise.
 */
class Insert extends DatabaseCommand implements CarrierInterface
{
    use ContextTrait;

    // Special identifier to forward insert key into
    public const INSERT_ID = '@lastInsertID';

    /** @var array */
    private $data;

    /**
     * @param DatabaseInterface $db
     * @param string            $table
     * @param array             $data
     */
    public function __construct(DatabaseInterface $db, string $table, array $data = [])
    {
        parent::__construct($db, $table);
        $this->data = $data;
    }

    /**
     * @inheritdoc
     */
    public function isReady(): bool
    {
        return empty($this->waitContext);
    }

    /**
     * @inheritdoc
     */
    public function accept(
        string $key,
        ?string $value,
        bool $handled = false,
        int $type = self::DATA
    ) {
        if (!$handled || !is_null($value)) {
            $this->freeContext($key);
        }

        $this->setContext($key, $value);
    }

    /**
     * Insert values, context not included.
     *
     * @return array
     */
    public function getData(): array
    {
        return array_merge($this->data, $this->context);
    }

    /**
     * @invisible
     */
    private $target;
    private $targetColumn;

    public function onInsert($target, $column)
    {
        $this->target = $target;
        $this->targetColumn = $column;
    }

    /**
     * Insert data into associated table.
     */
    public function execute()
    {
        $insertID = $this->db->insert($this->table)->values($this->getData())->run();

        // todo: forwarding keys

        if (!empty($this->target)) {
            call_user_func([$this->target, 'accept'], $this->targetColumn, $insertID);
        }

        parent::execute();
    }
}