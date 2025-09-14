<?php
require_once 'app/Services/ApiService.php';

// Test de connexion et mise à jour d'un utilisateur
$apiService = new ApiService();

echo "=== TEST API ===\n";

// Test de connexion
echo "1. Test de connexion...\n";
$loginResult = $apiService->login("admin", "admin123");
if ($loginResult["success"]) {
    echo "✓ Connexion réussie\n";
    echo "Token: " . substr($loginResult["token"], 0, 20) . "...\n";
} else {
    echo "✗ Échec de connexion: " . $loginResult["message"] . "\n";
    exit;
}

// Test de mise à jour d'un utilisateur (ID 1)
echo "\n2. Test de mise à jour utilisateur...\n";
$userData = [
    "firstName" => "Test",
    "lastName" => "User",
    "name" => "User",
    "email" => "test@example.com",
    "username" => "testuser",
    "phone" => "0123456789",
    "role" => "Archer",
    "licenceNumber" => "12345",
    "ageCategory" => "Senior",
    "arrivalYear" => "2023",
    "bowType" => "Recurve",
    "birthDate" => "1990-01-01",
    "gender" => "Masculin"
];

$updateResult = $apiService->updateUser(1, $userData);
if ($updateResult["success"]) {
    echo "✓ Mise à jour réussie\n";
    echo "Message: " . $updateResult["message"] . "\n";
} else {
    echo "✗ Échec de mise à jour: " . $updateResult["message"] . "\n";
}

echo "\n=== FIN TEST ===\n";
?> 