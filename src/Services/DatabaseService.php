<?php

namespace App\Services;

use PDO;
use PDOException;

class DatabaseService
{
    private $pdo;
    private $config;

    public function __construct($config)
    {
        $this->config = $config;
        $this->connect();
    }

    /**
     * Establish database connection
     */
    private function connect()
    {
        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $this->config['database']['host'],
                $this->config['database']['port'],
                $this->config['database']['database'],
                $this->config['database']['charset']
            );

            $this->pdo = new PDO(
                $dsn,
                $this->config['database']['username'],
                $this->config['database']['password'],
                $this->config['database']['options']
            );
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new \Exception("Database connection failed");
        }
    }

    /**
     * Get PDO instance
     */
    public function getPdo()
    {
        return $this->pdo;
    }

    /**
     * Execute a prepared statement (secure implementation)
     */
    public function executeSecure($sql, $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            throw new \Exception("Database operation failed");
        }
    }

    /**
     * Execute raw SQL (vulnerable implementation)
     */
    public function executeVulnerable($sql)
    {
        try {
            return $this->pdo->query($sql);
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            throw new \Exception("Database operation failed");
        }
    }

    /**
     * Fetch all results from a query
     */
    public function fetchAll($sql, $params = [], $vulnerable = false)
    {
        if ($vulnerable && isVulnerable('sql_injection')) {
            $stmt = $this->executeVulnerable($sql);
        } else {
            $stmt = $this->executeSecure($sql, $params);
        }
        
        return $stmt->fetchAll();
    }

    /**
     * Fetch single result from a query
     */
    public function fetchOne($sql, $params = [], $vulnerable = false)
    {
        if ($vulnerable && isVulnerable('sql_injection')) {
            $stmt = $this->executeVulnerable($sql);
        } else {
            $stmt = $this->executeSecure($sql, $params);
        }
        
        return $stmt->fetch();
    }

    /**
     * Insert record and return last insert ID
     */
    public function insert($sql, $params = [])
    {
        $stmt = $this->executeSecure($sql, $params);
        return $this->pdo->lastInsertId();
    }

    /**
     * Update or delete records and return affected rows
     */
    public function execute($sql, $params = [])
    {
        $stmt = $this->executeSecure($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Begin transaction
     */
    public function beginTransaction()
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit()
    {
        return $this->pdo->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback()
    {
        return $this->pdo->rollback();
    }

    /**
     * Check if database exists and create if not
     */
    public function createDatabaseIfNotExists()
    {
        try {
            $dbName = $this->config['database']['database'];
            $dsn = sprintf(
                'mysql:host=%s;port=%s;charset=%s',
                $this->config['database']['host'],
                $this->config['database']['port'],
                $this->config['database']['charset']
            );

            $pdo = new PDO(
                $dsn,
                $this->config['database']['username'],
                $this->config['database']['password']
            );

            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            return true;
        } catch (PDOException $e) {
            error_log("Failed to create database: " . $e->getMessage());
            return false;
        }
    }
}