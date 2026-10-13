<?php
/**
 * PDO singleton for MySQL.
 */
require_once __DIR__ . '/app_config.php';

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = app_config();
    $c = $config['db'];
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $c['host'],
        $c['port'],
        $c['name'],
        $c['charset']
    );

    $pdo = new PDO($dsn, $c['user'], $c['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
    ]);

    $tz = (string) (($config['app']['timezone'] ?? '') ?: 'Asia/Kolkata');
    if ($tz === 'Asia/Kolkata') {
        $pdo->exec("SET time_zone = '+05:30'");
    } elseif (preg_match('/^[\w\/]+$/', $tz)) {
        try {
            $pdo->exec('SET time_zone = ' . $pdo->quote($tz));
        } catch (Throwable $e) {
            $pdo->exec("SET time_zone = '+05:30'");
        }
    }

    return $pdo;
}
