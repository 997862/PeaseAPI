<?php

namespace NewApi\Database;

use PDO;
use PDOException;

class Connection
{
    private static ?PDO $instance = null;
    private static ?PDO $logInstance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::$instance = self::createConnection();
        }
        return self::$instance;
    }

    public static function getLogInstance(): PDO
    {
        if (self::$logInstance === null) {
            $logDsn = getenv('LOG_SQL_DSN');
            if ($logDsn) {
                self::$logInstance = self::createConnectionFromDsn($logDsn);
            } else {
                self::$logInstance = self::getInstance();
            }
        }
        return self::$logInstance;
    }

    private static function env(string $key, ?string $default = null): ?string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;
        if ($value === null) {
            $value = getenv($key) ?: $default;
        }
        return $value;
    }

    private static function createConnection(): PDO
    {
        $dbType = self::env('DB_TYPE', 'mysql');
        $dbHost = self::env('DB_HOST', '127.0.0.1');
        $dbPort = self::env('DB_PORT', '3306');
        $dbName = self::env('DB_DATABASE', 'new_api');
        $dbUser = self::env('DB_USERNAME', 'root');
        $dbPass = self::env('DB_PASSWORD', '');
        $dbCharset = self::env('DB_CHARSET', 'utf8mb4');

        if ($dbType === 'sqlite') {
            $dbPath = self::env('DB_PATH', '/data/new-api.db');
            return self::createConnectionFromDsn("sqlite:$dbPath");
        } elseif ($dbType === 'postgres') {
            $dsn = "pgsql:host=$dbHost;port=$dbPort;dbname=$dbName";
            return self::createConnectionFromDsn($dsn, $dbUser, $dbPass);
        } else {
            $dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=$dbCharset";
            return self::createConnectionFromDsn($dsn, $dbUser, $dbPass);
        }
    }

    private static function createConnectionFromDsn(string $dsn, ?string $username = null, ?string $password = null): PDO
    {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => true,
        ];

        if (getenv('APP_ENV') === 'development') {
            $options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_WARNING;
        }

        try {
            $pdo = new PDO($dsn, $username, $password, $options);
            return $pdo;
        } catch (PDOException $e) {
            throw new \RuntimeException("Database connection failed: " . $e->getMessage(), 0, $e);
        }
    }

    public static function beginTransaction(): bool
    {
        return self::getInstance()->beginTransaction();
    }

    public static function commit(): bool
    {
        return self::getInstance()->commit();
    }

    public static function rollBack(): bool
    {
        return self::getInstance()->rollBack();
    }

    public static function transaction(callable $callback): mixed
    {
        self::beginTransaction();
        try {
            $result = $callback(self::getInstance());
            self::commit();
            return $result;
        } catch (\Exception $e) {
            self::rollBack();
            throw $e;
        }
    }

    public static function reset(): void
    {
        self::$instance = null;
        self::$logInstance = null;
    }
}
