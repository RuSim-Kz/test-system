<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

try {
    $pdo = getDbConnection();
    
    // Получить общую статистику
    $stats = [];
    
    // Количество товаров
    $stmt = $pdo->query("SELECT COUNT(*) as total_products FROM products");
    $stats['total_products'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_products'];
    
    // Количество категорий
    $stmt = $pdo->query("SELECT COUNT(*) as total_categories FROM categories");
    $stats['total_categories'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_categories'];
    
    // Общее количество заказов
    $stmt = $pdo->query("SELECT COUNT(*) as total_orders FROM orders");
    $stats['total_orders'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_orders'];
    
    // Общая выручка
    $stmt = $pdo->query("SELECT COALESCE(SUM(total_price), 0) as total_revenue FROM orders");
    $stats['total_revenue'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_revenue'];
    
    // Последний заказ
    $stmt = $pdo->query("SELECT purchase_time FROM orders ORDER BY purchase_time DESC LIMIT 1");
    $lastOrder = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['last_order_time'] = $lastOrder ? $lastOrder['purchase_time'] : null;
    
    // Первый заказ
    $stmt = $pdo->query("SELECT purchase_time FROM orders ORDER BY purchase_time ASC LIMIT 1");
    $firstOrder = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['first_order_time'] = $firstOrder ? $firstOrder['purchase_time'] : null;
    
    // Статистика по категориям
    $stmt = $pdo->query("
        SELECT 
            c.id,
            c.name as category_name,
            COUNT(p.id) as products_count,
            COALESCE(SUM(o.quantity), 0) as total_quantity_sold,
            COALESCE(SUM(o.total_price), 0) as total_revenue
        FROM categories c
        LEFT JOIN products p ON c.id = p.category_id
        LEFT JOIN orders o ON p.id = o.product_id
        GROUP BY c.id, c.name
        ORDER BY total_revenue DESC
    ");
    $stats['category_details'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Топ товаров по продажам
    $stmt = $pdo->query("
        SELECT 
            p.id,
            p.name as product_name,
            c.name as category_name,
            COALESCE(SUM(o.quantity), 0) as total_quantity_sold,
            COALESCE(SUM(o.total_price), 0) as total_revenue,
            COUNT(o.id) as orders_count
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN orders o ON p.id = o.product_id
        GROUP BY p.id, p.name, c.name
        ORDER BY total_quantity_sold DESC
        LIMIT 10
    ");
    $stats['top_products'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Статистика по времени
    if ($stats['first_order_time'] && $stats['last_order_time']) {
        $firstTime = new DateTime($stats['first_order_time']);
        $lastTime = new DateTime($stats['last_order_time']);
        $interval = $firstTime->diff($lastTime);
        $stats['system_uptime'] = [
            'days' => $interval->days,
            'hours' => $interval->h,
            'minutes' => $interval->i,
            'total_seconds' => $interval->days * 86400 + $interval->h * 3600 + $interval->i * 60
        ];
    } else {
        $stats['system_uptime'] = null;
    }
    
    // Проверка здоровья системы
    $health = [];
    
    // Проверка подключения к БД
    $health['database'] = 'healthy';
    
    // Проверка Redis (если доступен)
    try {
        $redis = getRedisConnection();
        if ($redis instanceof Redis) {
            $redis->ping();
            $health['redis'] = 'healthy';
        } else {
            $health['redis'] = 'file_lock_mode';
        }
    } catch (Exception $e) {
        $health['redis'] = 'unavailable';
    }
    
    // Проверка последней активности
    if ($stats['last_order_time']) {
        $lastOrderTime = new DateTime($stats['last_order_time']);
        $now = new DateTime();
        $diff = $now->diff($lastOrderTime);
        
        if ($diff->days > 1) {
            $health['activity'] = 'low';
        } elseif ($diff->h > 1) {
            $health['activity'] = 'medium';
        } else {
            $health['activity'] = 'high';
        }
    } else {
        $health['activity'] = 'no_activity';
    }
    
    $stats['system_health'] = $health;
    
    echo json_encode([
        'success' => true,
        'data' => $stats,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка получения информации о системе: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
}
?> 