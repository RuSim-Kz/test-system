<?php
require_once 'config.php';

// Устанавливаем заголовки для JSON ответа
header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = getDbConnection();
    
    // Создаем таблицы
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS categories (
            id SERIAL PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT
        )
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS products (
            id SERIAL PRIMARY KEY,
            name VARCHAR(200) NOT NULL,
            description TEXT,
            price DECIMAL(10,2) NOT NULL,
            category_id INTEGER REFERENCES categories(id),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS orders (
            id SERIAL PRIMARY KEY,
            product_id INTEGER REFERENCES products(id),
            quantity INTEGER NOT NULL,
            total_price DECIMAL(10,2) NOT NULL,
            customer_email VARCHAR(255) NOT NULL,
            purchase_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS statistics (
            id SERIAL PRIMARY KEY,
            metric_name VARCHAR(100) NOT NULL,
            metric_value TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Проверяем, есть ли уже данные
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM categories");
    $categoryCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($categoryCount == 0) {
        // Добавляем категории
        $categories = [
            ['name' => 'Электроника', 'description' => 'Компьютеры, телефоны, планшеты'],
            ['name' => 'Одежда', 'description' => 'Мужская и женская одежда'],
            ['name' => 'Книги', 'description' => 'Художественная и техническая литература'],
            ['name' => 'Спорт', 'description' => 'Спортивные товары и инвентарь'],
            ['name' => 'Дом и сад', 'description' => 'Товары для дома и сада']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (:name, :description)");
        foreach ($categories as $category) {
            $stmt->execute($category);
        }
        
        // Добавляем продукты
        $products = [
            ['name' => 'iPhone 15', 'description' => 'Смартфон Apple', 'price' => 899.99, 'category_id' => 1],
            ['name' => 'MacBook Pro', 'description' => 'Ноутбук Apple', 'price' => 1999.99, 'category_id' => 1],
            ['name' => 'Футболка', 'description' => 'Хлопковая футболка', 'price' => 29.99, 'category_id' => 2],
            ['name' => 'Джинсы', 'description' => 'Классические джинсы', 'price' => 79.99, 'category_id' => 2],
            ['name' => 'Война и мир', 'description' => 'Роман Льва Толстого', 'price' => 19.99, 'category_id' => 3],
            ['name' => 'Гарри Поттер', 'description' => 'Фэнтези роман', 'price' => 24.99, 'category_id' => 3],
            ['name' => 'Футбольный мяч', 'description' => 'Профессиональный мяч', 'price' => 49.99, 'category_id' => 4],
            ['name' => 'Гантели', 'description' => 'Набор гантелей 10кг', 'price' => 89.99, 'category_id' => 4],
            ['name' => 'Ваза', 'description' => 'Керамическая ваза', 'price' => 39.99, 'category_id' => 5],
            ['name' => 'Лопата', 'description' => 'Садовая лопата', 'price' => 19.99, 'category_id' => 5]
        ];
        
        $stmt = $pdo->prepare("INSERT INTO products (name, description, price, category_id) VALUES (:name, :description, :price, :category_id)");
        foreach ($products as $product) {
            $stmt->execute($product);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'База данных инициализирована успешно',
            'data' => [
                'categories_created' => count($categories),
                'products_created' => count($products),
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'База данных уже содержит данные',
            'data' => [
                'categories_count' => $categoryCount,
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка инициализации: ' . $e->getMessage()
    ]);
}
?> 