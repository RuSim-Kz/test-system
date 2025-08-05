<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

// Проверка метода запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Метод не поддерживается. Используйте POST.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Проверка подтверждения
$input = json_decode(file_get_contents('php://input'), true);
$confirmation = $input['confirmation'] ?? '';

if ($confirmation !== 'CLEAR_ALL_DATA') {
    echo json_encode([
        'success' => false,
        'message' => 'Требуется подтверждение. Отправьте {"confirmation": "CLEAR_ALL_DATA"}'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo = getDbConnection();
    
    // Начать транзакцию
    $pdo->beginTransaction();
    
    // Получить статистику перед очисткой
    $stmt = $pdo->query("SELECT COUNT(*) as total_orders FROM orders");
    $totalOrders = $stmt->fetch(PDO::FETCH_ASSOC)['total_orders'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total_products FROM products");
    $totalProducts = $stmt->fetch(PDO::FETCH_ASSOC)['total_products'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total_categories FROM categories");
    $totalCategories = $stmt->fetch(PDO::FETCH_ASSOC)['total_categories'];
    
    // Очистить таблицы в правильном порядке (из-за внешних ключей)
    $pdo->exec("DELETE FROM orders");
    $pdo->exec("DELETE FROM products");
    $pdo->exec("DELETE FROM categories");
    
    // Сбросить автоинкремент
    $pdo->exec("ALTER SEQUENCE orders_id_seq RESTART WITH 1");
    $pdo->exec("ALTER SEQUENCE products_id_seq RESTART WITH 1");
    $pdo->exec("ALTER SEQUENCE categories_id_seq RESTART WITH 1");
    
    // Зафиксировать транзакцию
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'База данных успешно очищена',
        'data' => [
            'deleted_orders' => $totalOrders,
            'deleted_products' => $totalProducts,
            'deleted_categories' => $totalCategories,
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // Откатить транзакцию в случае ошибки
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка при очистке базы данных: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?> 