<?php
// Test direct de l'API topics/list
session_start();

// Simuler une session utilisateur
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = [
        'id' => 1,
        'username' => 'admin',
        'token' => 'test-token'
    ];
}

$apiUrl = 'https://arctraining.fr/api/topics/list';

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . ($_SESSION['user']['token'] ?? ''),
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Test Topics API</title></head><body>";
echo "<h1>Test API Topics List</h1>";
echo "<h2>HTTP Code: $httpCode</h2>";

if ($error) {
    echo "<p style='color: red;'><strong>Erreur CURL:</strong> $error</p>";
}

echo "<h3>Réponse brute:</h3>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

$data = json_decode($response, true);
if ($data) {
    echo "<h3>Données décodées:</h3>";
    echo "<pre>" . print_r($data, true) . "</pre>";
    
    if (isset($data['data']) && is_array($data['data'])) {
        echo "<h3>Topics trouvés: " . count($data['data']) . "</h3>";
        foreach ($data['data'] as $topic) {
            echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
            echo "<strong>Topic ID:</strong> " . ($topic['id'] ?? 'N/A') . "<br>";
            echo "<strong>Title:</strong> " . ($topic['title'] ?? 'N/A') . "<br>";
            echo "<strong>Group ID:</strong> " . var_export($topic['group_id'] ?? 'N/A', true) . " (" . gettype($topic['group_id'] ?? '') . ")<br>";
            echo "<strong>Created by:</strong> " . ($topic['created_by_name'] ?? 'N/A') . "<br>";
            echo "<strong>Toutes les clés:</strong> " . implode(', ', array_keys($topic)) . "<br>";
            echo "</div>";
        }
    }
}

echo "</body></html>";
