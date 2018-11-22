<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;
use Spiral\Database\Config\DatabaseConfig;
use Spiral\Database\Database;
use Spiral\Database\DatabaseManager;
use Spiral\Database\Driver\AbstractDriver;
use Spiral\Database\Driver\AbstractHandler;
use Spiral\ORM\ORM;

// todo: TEST WITH FK
// todo: TEST WITH
abstract class BaseTest extends TestCase
{
    // tests configuration
    public static $config;

    // currently active driver
    public const DRIVER = null;

    // cross test driver cache
    public static $driverCache = [];

    /** @var AbstractDriver */
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

        $this->orm = new ORM($this->dbal);

        if (self::$config['debug']) {
            $this->enableProfiling();
        }

        $this->logger = new TestLogger();
        $this->getDriver()->setLogger($this->logger);
    }

    /**
     * Cleanup.
     */
    public function tearDown()
    {
        $this->disableProfiling();
        $this->dropDatabase($this->dbal->database('default'));
        $this->orm = null;
        $this->dbal = null;
    }

    /**
     * @return AbstractDriver
     */
    public function getDriver(): AbstractDriver
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

        $this->assertSame(
            $numWrites,
            $queries,
            "Number of write SQL queries do not match, expected {$numWrites} got {$queries}."
        );
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

        $this->assertSame(
            $numReads,
            $queries,
            "Number of write SQL queries do not match, expected {$numReads} got {$queries}."
        );
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

            $schema->save(AbstractHandler::DROP_FOREIGN_KEYS);
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
                $this->countReads++;
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
            if (strpos($message, 'SELECT') === 0) {
                echo " \n> \033[32m" . $message . "\033[0m";
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
}