<?php
// Конфигурация базы данных PostgreSQL
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'your_database_name');
define('DB_USER', getenv('DB_USER') ?: 'your_username');
define('DB_PASS', getenv('DB_PASSWORD') ?: 'your_password');
define('DB_PORT', getenv('DB_PORT') ?: '5432');

// Конфигурация Redis
define('REDIS_HOST', getenv('REDIS_HOST') ?: 'localhost');
define('REDIS_PORT', getenv('REDIS_PORT') ?: 6379);
define('REDIS_PASS', getenv('REDIS_PASSWORD') ?: ''); // Если есть пароль

// Функция подключения к PostgreSQL
function getDbConnection() {
    try {
        $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die("Ошибка подключения к базе данных: " . $e->getMessage());
    }
}

// Функция подключения к Redis
function getRedisConnection() {
    try {
        $redis = new Redis();
        $redis->connect(REDIS_HOST, REDIS_PORT);
        if (!empty(REDIS_PASS)) {
            $redis->auth(REDIS_PASS);
        }
        return $redis;
    } catch (Exception $e) {
        die("Ошибка подключения к Redis: " . $e->getMessage());
    }
}
?> 