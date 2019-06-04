<?php
/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
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
use Cycle\ORM\Transaction;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;
use Spiral\Database\Config\DatabaseConfig;
use Spiral\Database\Database;
use Spiral\Database\DatabaseManager;
use Spiral\Database\Driver\Driver;
use Spiral\Database\Driver\Handler;

abstract class BaseTest extends TestCase
{
    // tests configuration
    public static $config;

    // currently active driver
    public const DRIVER = null;

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
    public function setUp()
    {
        parent::setUp();

        $this->dbal = new DatabaseManager(new DatabaseConfig());
        $this->dbal->addDatabase(new Database(
            'default',
            '',
            $this->getDriver()
        ));

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

        $this->orm = new ORM(new Factory(
            $this->dbal,
            RelationConfig::getDefault()
        ));

        // use promises by default
        $this->orm = $this->orm->withProxyFactory(new PromiseFactory());
    }

    /**
     * Cleanup.
     */
    public function tearDown()
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

            $this->driver = new $class([
                'connection' => $config['conn'],
                'username'   => $config['user'],
                'password'   => $config['pass'],
                'options'    => []
            ]);
        }

        $this->driver->setProfiling(true);

        return static::$driverCache[static::DRIVER] = $this->driver;
    }

    /**
     * Start counting update/insert/delete queries.
     */
    public function captureWriteQueries()
    {
        $this->numWrites = $this->logger->countWriteQueries();
    }

    /**
     * @param int $numWrites
     */
    public function assertNumWrites(int $numWrites)
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
    public function captureReadQueries()
    {
        $this->numReads = $this->logger->countReadQueries();
    }

    /**
     * @param int $numReads
     */
    public function assertNumReads(int $numReads)
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
    protected function dropDatabase(Database $database = null)
    {
        if (empty($database)) {
            return;
        }

        foreach ($database->getTables() as $table) {
            $schema = $table->getSchema();

            foreach ($schema->getForeignKeys() as $foreign) {
                $schema->dropForeignKey($foreign->getColumn());
            }

            $schema->save(Handler::DROP_FOREIGN_KEYS);
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
    protected function enableProfiling()
    {
        if (!is_null($this->logger)) {
            $this->logger->display();
        }
    }

    /**
     * For debug purposes only.
     */
    protected function disableProfiling()
    {
        if (!is_null($this->logger)) {
            $this->logger->hide();
        }
    }

    protected function save($e)
    {
        $tr = new Transaction($this->orm);
        $tr->persist($e);
        $tr->run();
    }

    protected function assertSQL($expected, $given)
    {
        $expected = preg_replace("/[ \s\'\[\]\"]+/", ' ', $expected);
        $given = preg_replace("/[ \s'\[\]\"]+/", ' ', $given);
        $this->assertSame($expected, $given);
    }

    protected function assertClearState(ORM $orm)
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
    ) {
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

class TestLogger implements LoggerInterface
{
    use LoggerTrait;

    private $display;

    private $countWrites;
    private $countReads;

    public function __construct()
    {
        $this->countWrites = 0;
        $this->countReads = 0;
    }

    public function countWriteQueries(): int
    {
        return $this->countWrites;
    }

    public function countReadQueries(): int
    {
        return $this->countReads;
    }

    public function log($level, $message, array $context = [])
    {
        if (!empty($context['query'])) {
            $sql = strtolower($context['query']);
            if (
                strpos($sql, 'insert') === 0
                || strpos($sql, 'update') === 0
                || strpos($sql, 'delete') === 0
            ) {
                $this->countWrites++;
            } else {
                if (!$this->isPostgresSystemQuery($sql)) {
                    $this->countReads++;
                }
            }
        }

        if (!$this->display) {
            return;
        }

        if ($level == LogLevel::ERROR) {
            echo " \n! \033[31m" . $message . "\033[0m";
        } elseif ($level == LogLevel::ALERT) {
            echo " \n! \033[35m" . $message . "\033[0m";
        } elseif (strpos($message, 'SHOW') === 0) {
            echo " \n> \033[34m" . $message . "\033[0m";
        } else {
            if ($this->isPostgresSystemQuery($message)) {
                echo " \n> \033[90m" . $message . "\033[0m";

                return;
            }

            if (strpos($message, 'SELECT') === 0) {
                echo " \n> \033[32m" . $message . "\033[0m";
            } elseif (strpos($message, 'INSERT') === 0) {
                echo " \n> \033[36m" . $message . "\033[0m";
            } else {
                echo " \n> \033[33m" . $message . "\033[0m";
            }
        }
    }

    public function display()
    {
        $this->display = true;
    }

    public function hide()
    {
        $this->display = false;
    }

    protected function isPostgresSystemQuery(string $query): bool
    {
        $query = strtolower($query);
        if (
            strpos($query, 'tc.constraint_name')
            || strpos($query, 'pg_indexes')
            || strpos($query, 'tc.constraint_name')
            || strpos($query, 'pg_constraint')
            || strpos($query, 'information_schema')
            || strpos($query, 'pg_class')
        ) {
            return true;
        }

        return false;
    }
}