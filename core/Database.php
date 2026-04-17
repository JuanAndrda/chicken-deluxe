<?php
/**
 * Database class — handles master-slave connection routing
 * All writes (INSERT/UPDATE/DELETE) go to Master (port 3306)
 * All reads (SELECT) go to Slave (port 3307)
 */
class Database
{
    private static ?Database $instance = null;
    private PDO $master;
    private PDO $slave;

    /** Create both master and slave PDO connections */
    private function __construct()
    {
        $config = require __DIR__ . '/../config/database.php';

        $masterDsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['master']['host'],
            $config['master']['port'],
            $config['master']['dbname'],
            $config['master']['charset']
        );

        $slaveDsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['slave']['host'],
            $config['slave']['port'],
            $config['slave']['dbname'],
            $config['slave']['charset']
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $this->master = new PDO(
            $masterDsn,
            $config['master']['username'],
            $config['master']['password'],
            $options
        );

        $this->slave = new PDO(
            $slaveDsn,
            $config['slave']['username'],
            $config['slave']['password'],
            $options
        );
    }

    /** Get the singleton instance */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /** Execute a SELECT query on the Slave */
    public function read(string $sql, array $params = []): array
    {
        $stmt = $this->slave->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Execute a SELECT query and return a single row from the Slave */
    public function readOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->slave->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Execute an INSERT/UPDATE/DELETE query on the Master */
    public function write(string $sql, array $params = []): int
    {
        $stmt = $this->master->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /** Execute an INSERT on the Master and return the last inserted ID */
    public function insert(string $sql, array $params = []): int
    {
        $stmt = $this->master->prepare($sql);
        $stmt->execute($params);
        return (int) $this->master->lastInsertId();
    }

    /** Begin a transaction on the Master */
    public function beginTransaction(): void
    {
        $this->master->beginTransaction();
    }

    /** Commit the current Master transaction */
    public function commit(): void
    {
        $this->master->commit();
    }

    /** Roll back the current Master transaction */
    public function rollback(): void
    {
        $this->master->rollBack();
    }

    /** Get the raw Master PDO (use sparingly) */
    public function getMaster(): PDO
    {
        return $this->master;
    }

    /** Get the raw Slave PDO (use sparingly) */
    public function getSlave(): PDO
    {
        return $this->slave;
    }

    /** Prevent cloning */
    private function __clone() {}
}
