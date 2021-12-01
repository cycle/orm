<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Command\Helper;

use Cycle\ORM\Command\DatabaseCommand;
use Cycle\ORM\Command\Traits\ErrorTrait;
use Cycle\Database\DatabaseInterface;

class TestInsert extends DatabaseCommand
{
    use ErrorTrait;

    // Special identifier to forward insert key into
    public const INSERT_ID = '@lastInsertID';

    /** @var array */
    protected $data;

    /**
     * @param DatabaseInterface $db
     * @param string            $table
     * @param array             $data
     */
    public function __construct(DatabaseInterface $db, string $table, array $data = [], callable $generateID = null)
    {
        parent::__construct($db, $table);
        $this->data = $data;
    }

    /**
     * @inheritdoc
     */
    public function isReady(): bool
    {
        return true;
    }

    /**
     * Insert values, context not included.
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Insert data into associated table.
     */
    public function execute(): void
    {
        $data = $this->getData();
        $this->db->insert($this->table)->values($data)->run();
        parent::execute();
    }
}
