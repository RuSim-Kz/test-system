-- Создание базы данных и таблиц для системы заказов

-- Таблица категорий
CREATE TABLE categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Таблица продуктов
CREATE TABLE products (
    id SERIAL PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    category_id INTEGER REFERENCES categories(id),
    stock_quantity INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Таблица заказов
CREATE TABLE orders (
    id SERIAL PRIMARY KEY,
    product_id INTEGER REFERENCES products(id),
    quantity INTEGER NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    purchase_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    customer_email VARCHAR(255),
    status VARCHAR(50) DEFAULT 'completed'
);

-- Таблица статистики
CREATE TABLE statistics (
    id SERIAL PRIMARY KEY,
    category_id INTEGER REFERENCES categories(id),
    products_sold INTEGER DEFAULT 0,
    total_revenue DECIMAL(10,2) DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Заполнение категорий
INSERT INTO categories (name, description) VALUES
('Электроника', 'Смартфоны, ноутбуки, планшеты'),
('Одежда', 'Мужская и женская одежда'),
('Книги', 'Художественная и техническая литература'),
('Спорт', 'Спортивный инвентарь и одежда'),
('Дом и сад', 'Товары для дома и сада');

-- Заполнение продуктов
INSERT INTO products (name, description, price, category_id, stock_quantity) VALUES
('iPhone 15', 'Смартфон Apple', 89999.00, 1, 50),
('MacBook Pro', 'Ноутбук Apple', 199999.00, 1, 30),
('Футболка Nike', 'Спортивная футболка', 2999.00, 2, 100),
('Джинсы Levi''s', 'Классические джинсы', 5999.00, 2, 80),
('Война и мир', 'Роман Льва Толстого', 899.00, 3, 200),
('Python для начинающих', 'Учебник по программированию', 1299.00, 3, 150),
('Футбольный мяч', 'Профессиональный мяч', 3999.00, 4, 60),
('Гантели 5кг', 'Набор гантелей', 2499.00, 4, 40),
('Кофемашина', 'Автоматическая кофемашина', 15999.00, 5, 25),
('Садовые ножницы', 'Профессиональные ножницы', 1999.00, 5, 70);

-- Создание индексов для оптимизации
CREATE INDEX idx_orders_purchase_time ON orders(purchase_time);
CREATE INDEX idx_orders_product_id ON orders(product_id);
CREATE INDEX idx_products_category_id ON products(category_id); 