<?php
declare(strict_types=1);

// Update these values if your local or hosted MySQL configuration differs.
const DB_HOST = '127.0.0.1';
const DB_NAME = 'ontrack_db';
const DB_USER = 'root';
const DB_PASS = '';

function get_db_connection(): PDO
{
    // Try ontrack_db first, fallback to ionic_students_api if not present
    try {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        return new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        $dsnFallback = 'mysql:host=' . DB_HOST . ';dbname=ionic_students_api;charset=utf8mb4';
        return new PDO($dsnFallback, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
}
