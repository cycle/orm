<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Command\Database;

use Spiral\Database\DatabaseInterface;
use Spiral\ORM\Command\ContextualInterface;
use Spiral\ORM\Command\Database\Traits\ContextTrait;
use Spiral\ORM\Command\Database\Traits\WhereTrait;
use Spiral\ORM\Command\ScopedInterface;

/**
 * Ensure the link between two objects when non of this objects exists.
 */
class LinkCommand extends DatabaseCommand implements ContextualInterface, ScopedInterface
{
    use ContextTrait, WhereTrait;

    /** @var string */
    private $description;

    /**
     * @param DatabaseInterface $db
     * @param string            $table
     * @param string            $description
     */
    public function __construct(DatabaseInterface $db, string $table, string $description)
    {
        parent::__construct($db, $table);
        $this->description = $description;
    }

    /**
     * @inheritdoc
     */
    public function isReady(): bool
    {
        return !empty($this->context) && !empty($this->where);
    }

    /**
     * Update data in associated table.
     */
    public function execute()
    {
        $this->db->update($this->table, $this->context, $this->where)->run();
        parent::execute();
    }
}