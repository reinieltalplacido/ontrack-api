<?php
declare(strict_types=1);

/**
 * Resolve an environment variable from getenv(), $_ENV, or $_SERVER.
 */
function env_value(string ...$keys): ?string
{
    foreach ($keys as $key) {
        $val = getenv($key);
        if ($val !== false && $val !== '') {
            return (string)$val;
        }
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            return (string)$_ENV[$key];
        }
        if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
            return (string)$_SERVER[$key];
        }
    }
    return null;
}

function get_db_connection(): PDO
{
    $url = env_value('MYSQL_URL', 'MYSQL_PUBLIC_URL', 'DATABASE_URL');
    $hasEnvConfig = ($url !== null) || (env_value('DB_HOST', 'MYSQLHOST', 'MYSQL_HOST') !== null);

    if ($url !== null) {
        $parts = parse_url($url);
        $host = $parts['host'] ?? '127.0.0.1';
        $port = (string)($parts['port'] ?? '3306');
        $name = ltrim($parts['path'] ?? 'ontrack_db', '/');
        $user = rawurldecode($parts['user'] ?? 'root');
        $pass = rawurldecode($parts['pass'] ?? '');
    } else {
        $host = env_value('DB_HOST', 'MYSQLHOST', 'MYSQL_HOST') ?? '127.0.0.1';
        $port = env_value('DB_PORT', 'MYSQLPORT', 'MYSQL_PORT') ?? '3306';
        $name = env_value('DB_NAME', 'MYSQLDATABASE', 'MYSQL_DATABASE') ?? 'ontrack_db';
        $user = env_value('DB_USER', 'MYSQLUSER', 'MYSQL_USER') ?? 'root';
        $pass = env_value('DB_PASS', 'MYSQLPASSWORD', 'MYSQL_PASSWORD') ?? '';
    }

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, $options);
        $pdo->exec("SET time_zone = '+08:00'");
        return $pdo;
    } catch (PDOException $e) {
        // Only fall back to legacy local XAMPP DB name when no external DB env vars are configured
        if (!$hasEnvConfig) {
            $dsnFallback = "mysql:host={$host};port={$port};dbname=ionic_students_api;charset=utf8mb4";
            $pdo = new PDO($dsnFallback, $user, $pass, $options);
            $pdo->exec("SET time_zone = '+08:00'");
            return $pdo;
        }
        throw $e;
    }
}
