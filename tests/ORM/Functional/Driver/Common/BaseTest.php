<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common;

use Cycle\ORM\Collection\ArrayCollectionFactory;
use Cycle\ORM\Collection\DoctrineCollectionFactory;
use Cycle\ORM\Collection\Pivoted\PivotedStorage;
use Cycle\ORM\Config\RelationConfig;
use Cycle\ORM\Factory;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\ORM;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Reference\ReferenceInterface;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Tests\Fixtures\TestLogger;
use Cycle\ORM\Transaction;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;
use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\Database;
use Cycle\Database\DatabaseManager;
use Cycle\Database\Driver\Driver;
use Cycle\Database\Driver\HandlerInterface;

abstract class BaseTest extends TestCase
{
    // currently active driver
    public const DRIVER = null;

    // tests configuration
    public static $config;

    // cross test driver cache
    public static $driverCache = [];

    protected static $lastORM;

    /** @var Driver */
    protected $driver;

    /** @var DatabaseManager */
    protected $dbal;

    /** @var ORM */
    protected $orm;

    /** @var TestLogger */
    protected $logger;

    /** @var int */
    protected $numWrites;

    /** @var int */
    protected $numReads;

    /**
     * Init all we need.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->getDriver()->rollbackTransaction();

        $this->dbal = new DatabaseManager(new DatabaseConfig());
        $this->dbal->addDatabase(
            new Database(
                'default',
                '',
                $this->getDriver()
            )
        );

        $this->logger = new TestLogger();
        $this->getDriver()->setLogger($this->logger);

        if (self::$config['debug']) {
            $this->logger->display();
        }

        $this->logger = new TestLogger();
        $this->getDriver()->setLogger($this->logger);

        if (self::$config['debug']) {
            $this->logger->display();
        }

        $this->orm = new ORM(
            (new Factory(
                $this->dbal,
                RelationConfig::getDefault(),
                null,
                new DoctrineCollectionFactory()
            ))->withCollectionFactory('array', new ArrayCollectionFactory()),
            new Schema([])
        );
    }

    /**
     * Cleanup.
     */
    public function tearDown(): void
    {
        $this->assertClearState($this->orm);

        $this->disableProfiling();
        $this->dropDatabase($this->dbal->database('default'));
        $this->orm = null;
        $this->dbal = null;

        if (\function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    }

    public function withSchema(SchemaInterface $schema): ORMInterface
    {
        $this->orm = $this->orm->with(schema: $schema);
        return $this->orm;
    }

    public function getDriver(): Driver
    {
        if (isset(static::$driverCache[static::DRIVER])) {
            return static::$driverCache[static::DRIVER];
        }

        $config = self::$config[static::DRIVER];
        if (!isset($this->driver)) {
            $this->driver = $config->driver::create($config);
        }

        return static::$driverCache[static::DRIVER] = $this->driver;
    }

    /**
     * Start counting update/insert/delete queries.
     */
    public function captureWriteQueries(): void
    {
        $this->numWrites = $this->logger->countWriteQueries();
    }

    /**
     * @param int $numWrites
     */
    public function assertNumWrites(int $numWrites): void
    {
        $queries = $this->logger->countWriteQueries() - $this->numWrites;

        if (!empty(self::$config['strict'])) {
            $this->assertSame(
                $numWrites,
                $queries,
                "Number of write SQL queries do not match, expected {$numWrites} got {$queries}."
            );
        }
    }

    /**
     * Start counting update/insert/delete queries.
     */
    public function captureReadQueries(): void
    {
        $this->numReads = $this->logger->countReadQueries();
    }

    /**
     * @param int $numReads
     */
    public function assertNumReads(int $numReads): void
    {
        $queries = $this->logger->countReadQueries() - $this->numReads;

        if (!empty(self::$config['strict'])) {
            $this->assertSame(
                $numReads,
                $queries,
                "Number of read SQL queries do not match, expected {$numReads} got {$queries}."
            );
        }
    }

    /**
     * @return Database
     */
    protected function getDatabase(): Database
    {
        return $this->dbal->database('default');
    }

    /**
     * @param Database|null $database
     */
    protected function dropDatabase(Database $database = null): void
    {
        if ($database === null) {
            return;
        }

        foreach ($database->getTables() as $table) {
            $schema = $table->getSchema();

            foreach ($schema->getForeignKeys() as $foreign) {
                $schema->dropForeignKey($foreign->getColumns());
            }

            $schema->save(HandlerInterface::DROP_FOREIGN_KEYS);
        }

        foreach ($database->getTables() as $table) {
            $schema = $table->getSchema();
            $schema->declareDropped();
            $schema->save();
        }
    }

    /**
     * For debug purposes only.
     */
    protected function enableProfiling(): void
    {
        if (null !== $this->logger) {
            $this->logger->display();
        }
    }

    /**
     * For debug purposes only.
     */
    protected function disableProfiling(): void
    {
        if (null !== $this->logger) {
            $this->logger->hide();
        }
    }

    protected function save(object ...$entities): void
    {
        $tr = new Transaction($this->orm);
        foreach ($entities as $entity) {
            $tr->persist($entity);
        }
        $tr->run();
    }

    /**
     * Extract all data from Entity using mapper
     *
     * @return array<string, mixed|ReferenceInterface>
     */
    protected function extractEntity(object $entity): array
    {
        return $this->orm->getMapper($entity)->extract($entity);
    }

    protected function assertSQL($expected, $given): void
    {
        $expected = preg_replace("/[ \s\'\[\]\"]+/", ' ', $expected);
        $given = preg_replace("/[ \s'\[\]\"]+/", ' ', $given);
        $this->assertSame($expected, $given);
    }

    protected function assertClearState(ORM $orm): void
    {
        $r = new \ReflectionClass(Node::class);

        $rel = $r->getProperty('relations');
        $rel->setAccessible(true);

        $heap = $orm->getHeap();
        foreach ($heap as $entity) {
            $node = $heap->get($entity);
            $this->assertNotNull($node);

            $this->assertEntitySynced(
                $orm,
                $node->getRole(),
                $orm->getMapper($entity)->extract($entity),
                $node->getData(),
                $rel->getValue($node)
            );

            // all the states must be closed
            $this->assertNotEquals(Node::SCHEDULED_INSERT, $node);
            $this->assertNotEquals(Node::SCHEDULED_UPDATE, $node);
            $this->assertNotEquals(Node::SCHEDULED_DELETE, $node);
        }
    }

    protected function assertEntitySynced(
        ORMInterface $orm,
        string $eName,
        array $entity,
        array $stateData,
        array $relations
    ): void {
        foreach ($entity as $name => $eValue) {
            if (array_key_exists($name, $stateData)) {
                $this->assertEquals(
                    $eValue,
                    $stateData[$name],
                    "Entity and State are not in sync `{$eName}`.`{$name}`"
                );

                continue;
            }

            if (!array_key_exists($name, $relations)) {
                // something else
                continue;
            }

            $parent = $eName;
            $relation = null;
            $parentChain = [];
            do {
                $parentChain[] = $parent;
                $relationSchema = (array)$this->orm->getSchema()->define($parent, SchemaInterface::RELATIONS);
                if (array_key_exists($name, $relationSchema)) {
                    $relation = $relationSchema[$name];
                    break;
                }

                $parent = $this->orm->getSchema()->define($parent, SchemaInterface::PARENT);
            } while ($parent);

            $roles = implode('`, `', $parentChain);
            $this->assertNotNull($relation, "Relation `{$name}` is not found among roles `{$roles}`. Check `{$eName}` and its parent roles.");

            $rValue = $relations[$name];

            if ($relation[Relation::TYPE] === Relation::EMBEDDED) {
                // do not run integrity check for embedded nodes, they do not have their own node
                continue;
            }

            if ($rValue === $eValue) {
                return;
            }

            if ($rValue instanceof ReferenceInterface && $rValue->hasValue()) {
                $rValue = $rValue->getValue();
            }

            if ($rValue instanceof PivotedStorage) {
                $rValue = $rValue->getElements();
            }

            // extract Node collection
            if ($rValue instanceof Collection) {
                $rValue = array_values($rValue->toArray());
            }

            if ($eValue instanceof ReferenceInterface && $eValue->hasValue()) {
                $eValue = $eValue->getValue();
            }

            // extract Entity collection
            if ($eValue instanceof \Traversable) {
                $eArray = [];
                foreach ($eValue as $key => $value) {
                    $eArray[$key] = $value;
                }
                $eValue = $eArray;
                if ($rValue === null) {
                    $rValue = [];
                }
            }

            if ($rValue instanceof ReferenceInterface && $eValue instanceof ReferenceInterface) {
                $this->assertEquals(
                    $rValue->getScope(),
                    $eValue->getScope(),
                    "Entity and State are not in sync `{$eName}`.`{$name}` (Reference scope)"
                );
                $this->assertEquals(
                    $rValue->getRole(),
                    $eValue->getRole(),
                    "Entity and State are not in sync `{$eName}`.`{$name}` (Reference role)"
                );
            } else {
                $this->assertEquals(
                    $rValue,
                    $eValue,
                    "Entity and State are not in sync `{$eName}`.`{$name}`"
                );
            }
        }
    }
}
