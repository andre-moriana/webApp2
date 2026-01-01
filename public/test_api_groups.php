<?php
// Test direct de l'API groups/list
session_start();

if (!isset($_SESSION['user'])) {
    die("Non connecté");
}

$apiUrl = 'https://arctraining.fr/api/groups/list';
$token = $_SESSION['auth_token'] ?? '';

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<h1>Test API /api/groups/list</h1>";
echo "<p>HTTP Code: $httpCode</p>";

echo "<h2>Réponse brute :</h2>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

echo "<h2>Réponse formatée :</h2>";
$data = json_decode($response, true);
if ($data && !empty($data[0])) {
    echo "<h3>Premier groupe :</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    foreach ($data[0] as $key => $value) {
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($key) . "</strong></td>";
        echo "<td>" . htmlspecialchars(json_encode($value)) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}
