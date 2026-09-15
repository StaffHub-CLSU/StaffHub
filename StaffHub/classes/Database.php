<?php
/**
 * Database
 *
 * Wraps a PDO connection in a Singleton so the whole application shares
 * one connection. Provides small reusable helper methods (query, fetch,
 * fetchAll, insert, execute) so page scripts and other classes never have
 * to write raw PDO boilerplate or worry about SQL injection, since every
 * method uses prepared statements.
 */
require_once __DIR__ . '/../config/database.php';

class Database
{
    /** @var Database|null */
    private static ?Database $instance = null;

    /** @var PDO */
    private PDO $connection;

    /**
     * Constructor is private -> enforces Singleton (only one connection
     * ever exists for the whole request lifecycle).
     */
    private function __construct()
    {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Never leak credentials or raw DB errors to the client.
            error_log('Database connection failed: ' . $e->getMessage());
            die('A database connection error occurred. Please try again later.');
        }
    }

    /**
     * Returns the single shared instance of this class.
     */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }

    /**
     * Runs a prepared SELECT/UPDATE/etc statement and returns the PDOStatement.
     */
    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /** Returns a single row (or null). */
    public function fetch(string $sql, array $params = []): ?array
    {
        $row = $this->query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    /** Returns all matching rows as an array. */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    /** Runs an INSERT and returns the new row's auto-increment id. */
    public function insert(string $sql, array $params = []): string
    {
        $this->query($sql, $params);
        return $this->connection->lastInsertId();
    }

    /** Runs an UPDATE/DELETE and returns the number of affected rows. */
    public function execute(string $sql, array $params = []): int
    {
        return $this->query($sql, $params)->rowCount();
    }

    public function beginTransaction(): bool
    {
        return $this->connection->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->connection->commit();
    }

    public function rollBack(): bool
    {
        return $this->connection->rollBack();
    }

    // Prevent cloning and unserialization of the Singleton instance.
    private function __clone() {}
    public function __wakeup()
    {
        throw new Exception('Cannot unserialize a Database singleton.');
    }
}
