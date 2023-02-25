<?php

require_once __DIR__ . '/../bootstrap.php';

use App\Services\DatabaseService;

try {
    echo "Starting database migration...\n";
    
    $config = require __DIR__ . '/../config/app.php';
    $db = new DatabaseService($config);
    
    // Create database if it doesn't exist
    echo "Creating database if not exists...\n";
    $db->createDatabaseIfNotExists();
    
    // Read and execute schema
    echo "Executing schema...\n";
    $schema = file_get_contents(__DIR__ . '/schema.sql');
    
    // Split by semicolons and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $schema)));
    
    foreach ($statements as $statement) {
        if (!empty($statement) && !str_starts_with($statement, '--')) {
            try {
                $db->getPdo()->exec($statement);
                echo "✓ Executed: " . substr($statement, 0, 50) . "...\n";
            } catch (Exception $e) {
                echo "✗ Error executing statement: " . $e->getMessage() . "\n";
                echo "Statement: " . substr($statement, 0, 100) . "...\n";
            }
        }
    }
    
    echo "Database migration completed successfully!\n";
    
    // Verify tables were created
    echo "\nVerifying tables...\n";
    $tables = $db->fetchAll("SHOW TABLES");
    foreach ($tables as $table) {
        $tableName = array_values($table)[0];
        echo "✓ Table exists: {$tableName}\n";
    }
    
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}