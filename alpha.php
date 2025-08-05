<?php
require_once 'config.php';

// Устанавливаем заголовки для JSON ответа
header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = getDbConnection();
    
    // Файловая блокировка вместо Redis
    $lockFile = '/tmp/alpha_script_lock';
    $lockHandle = fopen($lockFile, 'w+');
    
    if (!$lockHandle) {
        throw new Exception("Не удалось создать файл блокировки");
    }
    
    // Пытаемся установить блокировку
    if (!flock($lockHandle, LOCK_EX | LOCK_NB)) {
        fclose($lockHandle);
        echo json_encode([
            'success' => false,
            'message' => 'Скрипт уже выполняется'
        ]);
        exit;
    }
    
    // Получаем случайный продукт
    $stmt = $pdo->query("SELECT id, name, price FROM products ORDER BY RANDOM() LIMIT 1");
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        throw new Exception("Нет доступных продуктов");
    }
    
    // Генерируем случайное количество товара (1-5)
    $quantity = rand(1, 5);
    $totalPrice = $product['price'] * $quantity;
    
    // Генерируем случайный email
    $emails = ['user1@example.com', 'user2@example.com', 'user3@example.com', 'user4@example.com', 'user5@example.com'];
    $customerEmail = $emails[array_rand($emails)];
    
    // Вставляем заказ в базу данных
    $stmt = $pdo->prepare("
        INSERT INTO orders (product_id, quantity, total_price, customer_email, purchase_time) 
        VALUES (:product_id, :quantity, :total_price, :customer_email, NOW())
    ");
    
    $stmt->execute([
        ':product_id' => $product['id'],
        ':quantity' => $quantity,
        ':total_price' => $totalPrice,
        ':customer_email' => $customerEmail
    ]);
    
    // Пауза в 1 секунду для демонстрации
    sleep(1);
    
    // Удаляем блокировку
    flock($lockHandle, LOCK_UN);
    fclose($lockHandle);
    
    echo json_encode([
        'success' => true,
        'message' => 'Заказ создан успешно',
        'data' => [
            'product' => $product['name'],
            'quantity' => $quantity,
            'total_price' => $totalPrice,
            'customer_email' => $customerEmail,
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
    
} catch (Exception $e) {
    // Удаляем блокировку в случае ошибки
    if (isset($lockHandle)) {
        flock($lockHandle, LOCK_UN);
        fclose($lockHandle);
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка: ' . $e->getMessage()
    ]);
}
?> 