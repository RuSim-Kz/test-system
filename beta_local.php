<?php
require_once 'config.php';

// Устанавливаем заголовки для JSON ответа
header('Content-Type: application/json; charset=utf-8');

// Получаем количество запусков из параметра
$n = isset($_GET['n']) ? (int)$_GET['n'] : 1000;

// Ограничиваем количество запусков для безопасности
if ($n > 10000) {
    $n = 10000;
}

if ($n < 1) {
    $n = 1;
}

try {
    // Массив для хранения результатов
    $results = [];
    $successCount = 0;
    $errorCount = 0;
    
    // Выполняем N запусков Alpha скрипта локально
    for ($i = 0; $i < $n; $i++) {
        try {
            // Включаем Alpha скрипт локально
            ob_start();
            include 'alpha.php';
            $response = ob_get_clean();
            
            $data = json_decode($response, true);
            if ($data && isset($data['success']) && $data['success']) {
                $successCount++;
                $results[] = $data;
            } else {
                $errorCount++;
                $results[] = ['error' => $response];
            }
        } catch (Exception $e) {
            $errorCount++;
            $results[] = ['error' => 'Exception: ' . $e->getMessage()];
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Выполнено $n запусков Alpha скрипта",
        'summary' => [
            'total_requests' => $n,
            'successful' => $successCount,
            'errors' => $errorCount,
            'timestamp' => date('Y-m-d H:i:s')
        ],
        'results' => $results
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка: ' . $e->getMessage()
    ]);
}
?> 