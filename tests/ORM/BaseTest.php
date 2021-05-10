<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests;

use Cycle\ORM\Config\RelationConfig;
use Cycle\ORM\Factory;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\ORM;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Promise\Collection\CollectionPromise;
use Cycle\ORM\Promise\PromiseFactory;
use Cycle\ORM\Promise\PromiseInterface;
use Cycle\ORM\Relation;
use Cycle\ORM\Relation\Pivoted\PivotedCollectionPromise;
use Cycle\ORM\Relation\Pivoted\PivotedStorage;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Tests\Fixtures\TestLogger;
use Cycle\ORM\Transaction;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;
use Spiral\Database\Config\DatabaseConfig;
use Spiral\Database\Database;
use Spiral\Database\DatabaseManager;
use Spiral\Database\Driver\Driver;
use Spiral\Database\Driver\HandlerInterface;

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
            new Factory(
                $this->dbal,
                RelationConfig::getDefault()
            )
        );

        // use promises by default
        $this->orm = $this->orm->withPromiseFactory(new PromiseFactory());
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

        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    }

    /**
     * Calculates missing parameters for typecasting.
     *
     * @param SchemaInterface $schema
     * @return ORM|\Cycle\ORM\ORMInterface
     */
    public function withSchema(SchemaInterface $schema)
    {
        $this->orm = $this->orm->withSchema($schema);
        return $this->orm;
    }

    /**
     * @return Driver
     */
    public function getDriver(): Driver
    {
        if (isset(static::$driverCache[static::DRIVER])) {
            return static::$driverCache[static::DRIVER];
        }

        $config = self::$config[static::DRIVER];
        if (!isset($this->driver)) {
            $class = $config['driver'];

            $this->driver = new $class(
                [
                    'connection' => $config['conn'],
                    'username'   => $config['user'],
                    'password'   => $config['pass'],
                    'options'    => [],
                    'queryCache' => true
                ]
            );
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
                "Number of write SQL queries do not match, expected {$numReads} got {$queries}."
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
        if (!is_null($this->logger)) {
            $this->logger->display();
        }
    }

    /**
     * For debug purposes only.
     */
    protected function disableProfiling(): void
    {
        if (!is_null($this->logger)) {
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
            $state = $heap->get($entity);
            $this->assertNotNull($state);

            $this->assertEntitySynced(
                $orm,
                $state->getRole(),
                $orm->getMapper($entity)->extract($entity),
                $state->getData(),
                $rel->getValue($state)
            );

            // all the states must be closed
            $this->assertNotEquals(Node::SCHEDULED_INSERT, $state);
            $this->assertNotEquals(Node::SCHEDULED_UPDATE, $state);
            $this->assertNotEquals(Node::SCHEDULED_DELETE, $state);
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

            $relation = $this->orm->getSchema()->defineRelation($eName, $name);
            if ($relation[Relation::TYPE] === Relation::EMBEDDED) {
                // do not run integrity check for embedded nodes, they do not have their own node
                continue;
            }

            $rValue = $relations[$name];

            if ($rValue instanceof PivotedStorage || $rValue instanceof \Cycle\ORM\Relation\Pivoted\PivotedPromise) {
                continue;
            }

            if ($eValue instanceof CollectionPromise || $eValue instanceof PivotedCollectionPromise) {
                if (!$eValue->isInitialized()) {
                    $eValue = $eValue->getPromise();
                } else {
                    // normalizing
                    if ($rValue instanceof PromiseInterface && $rValue->__loaded()) {
                        $rValue = $rValue->__resolve();
                    }
                }
            }

            if ($eValue instanceof Collection) {
                $eValue = $eValue->toArray();
                if ($rValue === null) {
                    $rValue = [];
                }
            }

            $this->assertEquals(
                $rValue,
                $eValue,
                "Entity and State are not in sync `{$eName}`.`{$name}`"
            );
        }
    }
}
