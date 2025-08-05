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
    // Получаем текущий URL для определения базового пути
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $baseUrl = $protocol . '://' . $host;
    $alphaUrl = $baseUrl . '/alpha.php';
    
    // Массив для хранения результатов
    $results = [];
    $successCount = 0;
    $errorCount = 0;
    
    // Создаем массив cURL handles
    $curlHandles = [];
    $multiHandle = curl_multi_init();
    
    // Создаем N cURL запросов
    for ($i = 0; $i < $n; $i++) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $alphaUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        curl_multi_add_handle($multiHandle, $ch);
        $curlHandles[] = $ch;
    }
    
    // Выполняем все запросы одновременно
    $active = null;
    do {
        $mrc = curl_multi_exec($multiHandle, $active);
    } while ($mrc == CURLM_CALL_MULTI_PERFORM);
    
    while ($active && $mrc == CURLM_OK) {
        if (curl_multi_select($multiHandle) != -1) {
            do {
                $mrc = curl_multi_exec($multiHandle, $active);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);
        }
    }
    
    // Собираем результаты
    foreach ($curlHandles as $ch) {
        $response = curl_multi_getcontent($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($httpCode == 200) {
            $data = json_decode($response, true);
            if ($data && isset($data['success']) && $data['success']) {
                $successCount++;
                $results[] = $data;
            } else {
                $errorCount++;
                $results[] = ['error' => $response];
            }
        } else {
            $errorCount++;
            $results[] = ['error' => 'HTTP Error: ' . $httpCode];
        }
        
        curl_multi_remove_handle($multiHandle, $ch);
        curl_close($ch);
    }
    
    curl_multi_close($multiHandle);
    
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