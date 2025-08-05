<?php
require_once 'config.php';

// Устанавливаем заголовки для JSON ответа
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

try {
    $pdo = getDbConnection();
    
    // Оптимизированный запрос для получения статистики по последним 100 заказам
    $query = "
        WITH last_100_orders AS (
            SELECT 
                o.id,
                o.purchase_time,
                o.quantity,
                o.total_price,
                p.name as product_name,
                c.id as category_id,
                c.name as category_name
            FROM orders o
            JOIN products p ON o.product_id = p.id
            JOIN categories c ON p.category_id = c.id
            ORDER BY o.purchase_time DESC
            LIMIT 100
        ),
        time_range AS (
            SELECT 
                MIN(purchase_time) as first_order_time,
                MAX(purchase_time) as last_order_time,
                COUNT(*) as total_orders
            FROM last_100_orders
        ),
        category_stats AS (
            SELECT 
                category_id,
                category_name,
                COUNT(*) as orders_count,
                SUM(quantity) as total_quantity,
                SUM(total_price) as total_revenue
            FROM last_100_orders
            GROUP BY category_id, category_name
            ORDER BY total_quantity DESC
        )
        SELECT 
            tr.first_order_time,
            tr.last_order_time,
            tr.total_orders,
            EXTRACT(EPOCH FROM (tr.last_order_time - tr.first_order_time)) as time_diff_seconds,
            COALESCE(
                json_agg(
                    json_build_object(
                        'category_id', cs.category_id,
                        'category_name', cs.category_name,
                        'orders_count', cs.orders_count,
                        'total_quantity', cs.total_quantity,
                        'total_revenue', cs.total_revenue
                    ) ORDER BY cs.total_quantity DESC
                ) FILTER (WHERE cs.category_id IS NOT NULL),
                '[]'::json
            ) as category_statistics
        FROM time_range tr
        LEFT JOIN category_stats cs ON true
        GROUP BY tr.first_order_time, tr.last_order_time, tr.total_orders
    ";
    
    $stmt = $pdo->query($query);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result) {
        throw new Exception("Нет данных для анализа");
    }
    
    // Форматируем время
    $firstOrderTime = $result['first_order_time'];
    $lastOrderTime = $result['last_order_time'];
    $timeDiffSeconds = (int)$result['time_diff_seconds'];
    
    // Конвертируем секунды в читаемый формат
    $timeDiffFormatted = '';
    if ($timeDiffSeconds > 0) {
        $hours = floor($timeDiffSeconds / 3600);
        $minutes = floor(($timeDiffSeconds % 3600) / 60);
        $seconds = $timeDiffSeconds % 60;
        
        if ($hours > 0) {
            $timeDiffFormatted .= "$hours ч ";
        }
        if ($minutes > 0) {
            $timeDiffFormatted .= "$minutes мин ";
        }
        $timeDiffFormatted .= "$seconds сек";
    } else {
        $timeDiffFormatted = "0 сек";
    }
    
    // Декодируем JSON статистики по категориям
    $categoryStats = json_decode($result['category_statistics'], true);
    
    // Подготавливаем ответ
    $response = [
        'success' => true,
        'data' => [
            'total_orders' => (int)$result['total_orders'],
            'time_range' => [
                'first_order' => $firstOrderTime,
                'last_order' => $lastOrderTime,
                'duration' => $timeDiffFormatted,
                'duration_seconds' => $timeDiffSeconds
            ],
            'category_statistics' => $categoryStats,
            'summary' => [
                'total_products_sold' => array_sum(array_column($categoryStats, 'total_quantity')),
                'total_revenue' => array_sum(array_column($categoryStats, 'total_revenue')),
                'categories_count' => count($categoryStats)
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
}
?> 