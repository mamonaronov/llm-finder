<?php
header('Content-Type: application/json');

// Получаем поисковый запрос
$input = json_decode(file_get_contents('php://input'), true);
$query = $input['query'] ?? $_POST['query'] ?? '';

if (empty($query)) {
    http_response_code(400);
    echo json_encode(['error' => 'Query is empty']);
    exit;
}

// URL Python-сервиса (имя сервиса из docker-compose)
$pythonServiceUrl = 'http://python-service:8000/search';

$data = [
    'query' => $query,
'top_k' => 5
];

$ch = curl_init($pythonServiceUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    http_response_code(500);
    echo json_encode(['error' => 'Search service failed']);
    exit;
}

echo $response;
