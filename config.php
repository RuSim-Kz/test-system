<?php
// Конфигурация базы данных PostgreSQL
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_PORT', '5432');

// Конфигурация Redis
define('REDIS_HOST', 'localhost');
define('REDIS_PORT', 6379);
define('REDIS_PASS', ''); // Если есть пароль

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